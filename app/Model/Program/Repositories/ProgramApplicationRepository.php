<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\Program\Block;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
use App\Model\Program\Exceptions\UserAttendsConflictingProgramException;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Throwable;

use function assert;

/**
 * Třída spravující přihlášky programů.
 */
class ProgramApplicationRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, ProgramApplication::class);
    }

    public function findByUserAndProgram(User $user, Program $program): ProgramApplication|null
    {
        return $this->getRepository()->findOneBy(['user' => $user, 'program' => $program]);
    }

    /**
     * Vrací počty účastníků a náhradníků programů.
     *
     * @param int[] $programsIds
     *
     * @return array<int, array{attendees: int, alternates: int}> počty podle id programu
     */
    public function countAttendeesAndAlternates(array $programsIds): array
    {
        if ($programsIds === []) {
            return [];
        }

        $result = $this->createQueryBuilder('pa')
            ->select('IDENTITY(pa.program) AS programId, pa.alternate AS alternate, COUNT(pa.id) AS applicationsCount')
            ->where('pa.program IN (:programsIds)')
            ->groupBy('pa.program')
            ->addGroupBy('pa.alternate')
            ->setParameter('programsIds', $programsIds)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($programsIds as $programId) {
            $counts[$programId] = ['attendees' => 0, 'alternates' => 0];
        }

        foreach ($result as $row) {
            $programId = (int) $row['programId'];
            $key       = $row['alternate'] ? 'alternates' : 'attendees';

            $counts[$programId][$key] = (int) $row['applicationsCount'];
        }

        return $counts;
    }

    /**
     * Vrací programy, na které je uživatel přihlášený.
     *
     * @return array<int, bool> příznak náhradníka podle id programu
     */
    public function findUserApplicationsAlternateFlags(User $user): array
    {
        $result = $this->createQueryBuilder('pa')
            ->select('IDENTITY(pa.program) AS programId, pa.alternate AS alternate')
            ->where('pa.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $flags = [];
        foreach ($result as $row) {
            $flags[(int) $row['programId']] = (bool) $row['alternate'];
        }

        return $flags;
    }

    /** @throws Throwable */
    public function save(ProgramApplication $programApplication): void
    {
        $this->em->wrapInTransaction(function (EntityManager $em) use ($programApplication): void {
            $program = $em->getRepository(Program::class)->find($programApplication->getProgram()->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $user           = $programApplication->getUser();
            $capacity       = $program->getBlockCapacity();
            $attendeesCount = $program->getAttendeesCount();
            $alternate      = $capacity !== null && $attendeesCount >= $capacity;

            if ($alternate && ! $program->getBlock()->isAlternatesAllowed()) {
                throw new ProgramCapacityOccupiedException();
            }

            if ($this->userAttendsSameProgram($user, $program)) {
                throw new UserAlreadyAttendsProgramException();
            }

            // Náhradnictví se kontroluje jen když by nová přihláška byla taky náhradnická.
            // Posun náhradníka na účastníka (po uvolnění kapacity) se tím nezablokuje.
            // Zámek na řádku programu výš zajišťuje, že souběžné požadavky nemůžou projít
            // kontrolou obě a založit tak dvě přihlášky na stejný program.
            if ($alternate && $this->userAlternatesSameProgram($user, $program)) {
                throw new UserAlreadyAttendsProgramException();
            }

            if ($this->userAttendsSameBlockProgram($user, $program->getBlock())) {
                throw new UserAlreadyAttendsBlockException();
            }

            if ($this->userAttendsOrAlternatesConflictingProgram($user, $program)) {
                throw new UserAttendsConflictingProgramException();
            }

            $programApplication->setAlternate($alternate);

            $em->persist($programApplication);

            if ($alternate) {
                return;
            }

            $program->setAttendeesCount($attendeesCount + 1);
            $em->persist($program);

            // náhradnictví ve stejném bloku se přihlášením na program ruší
            foreach ($this->findByUserAlternateAndBlock($user, $program->getBlock()) as $pa) {
                $em->remove($pa);
            }
        });
    }

    /** @throws Throwable */
    public function remove(ProgramApplication $programApplication): void
    {
        $this->em->wrapInTransaction(function (EntityManager $em) use ($programApplication): void {
            $program = $em->getRepository(Program::class)->find($programApplication->getProgram()->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $attendeesCount = $program->getAttendeesCount();

            if (! $programApplication->isAlternate()) {
                $program->setAttendeesCount($attendeesCount - 1);
                $this->em->persist($program);
            }

            $this->em->remove($programApplication);
        });
    }

    /** @return Collection<int, ProgramApplication> */
    private function findByUserAlternateAndBlock(User $user, Block $block): Collection
    {
        $result = $this->createQueryBuilder('pa')
            ->join('pa.program', 'p', 'WITH', 'p.block = :block')
            ->where('pa.user = :user')
            ->andWhere('pa.alternate = true')
            ->setParameter('block', $block)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function userAttendsSameProgram(User $user, Program $program): bool
    {
        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->where('pa.user = :user')
            ->andWhere('pa.program = :program')
            ->andWhere('pa.alternate = false')
            ->setParameter('user', $user)
            ->setParameter('program', $program)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * Je uživatel na programu veden jako náhradník?
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function userAlternatesSameProgram(User $user, Program $program): bool
    {
        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->where('pa.user = :user')
            ->andWhere('pa.program = :program')
            ->andWhere('pa.alternate = true')
            ->setParameter('user', $user)
            ->setParameter('program', $program)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function userAttendsSameBlockProgram(User $user, Block $block): bool
    {
        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->join('pa.program', 'p', 'WITH', 'p.block = :block')
            ->where('pa.user = :user')
            ->andWhere('pa.alternate = false')
            ->setParameter('user', $user)
            ->setParameter('block', $block)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    private function userAttendsOrAlternatesConflictingProgram(User $user, Program $program): bool
    {
        $start = $program->getStart();
        $end   = $program->getEnd();

        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->join('pa.program', 'p')
            ->join('p.block', 'b')
            ->where('pa.user = :user')
            ->andWhere('p != :program')
            ->andWhere("p.start < :end AND DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->setParameter('user', $user)
            ->setParameter('program', $program)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }
}
