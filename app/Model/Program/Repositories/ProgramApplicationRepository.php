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
use Throwable;

use function assert;

/**
 * Třída spravující přihlášky programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramApplicationRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, ProgramApplication::class);
    }

    public function findByUserAndProgram(User $user, Program $program): ?ProgramApplication
    {
        return $this->getRepository()->findOneBy(['user' => $user, 'program' => $program]);
    }

    /**
     * @throws Throwable
     */
    public function save(ProgramApplication $programApplication): void
    {
        $this->em->transactional(function (EntityManager $em) use ($programApplication): void {
            $program = $em->getRepository(Program::class)->find($programApplication->getProgram()->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $user      = $programApplication->getUser();
            $capacity  = $program->getBlockCapacity();
            $occupancy = $program->getOccupancy();

            if ($capacity !== null && $occupancy >= $capacity && ! $program->getBlock()->isAlternatesAllowed()) {
                throw new ProgramCapacityOccupiedException();
            } elseif ($capacity !== null && $occupancy >= $capacity) {
                $programApplication->setAlternate(true);
            }

            if ($this->userAttendsSameProgram($user, $program)) {
                throw new UserAlreadyAttendsProgramException();
            }

            if ($this->userAttendsSameBlockProgram($user, $program->getBlock())) {
                throw new UserAlreadyAttendsBlockException();
            }

            if ($this->userAttendsOrAlternatesConflictingProgram($user, $program)) {
                throw new UserAttendsConflictingProgramException();
            }

            $this->em->persist($programApplication);

            if (! $programApplication->isAlternate()) {
                $program->setOccupancy($occupancy + 1);
                $this->em->persist($program);

                foreach ($this->findByUserAlternateAndBlock($user, $program->getBlock()) as $pa) {
                    $this->em->remove($pa);
                }
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function remove(ProgramApplication $programApplication): void
    {
        $this->em->transactional(function (EntityManager $em) use ($programApplication): void {
            $program = $em->getRepository(Program::class)->find($programApplication->getProgram()->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $occupancy = $program->getOccupancy();

            if (! $programApplication->isAlternate()) {
                $program->setOccupancy($occupancy - 1);
                $this->em->persist($program);
            }

            $this->em->remove($programApplication);
        });
    }

    /**
     * @return Collection<ProgramApplication>
     */
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
