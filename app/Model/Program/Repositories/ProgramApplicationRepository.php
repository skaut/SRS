<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use function assert;

/**
 * Třída spravující přihlášky programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramApplicationRepository extends EntityRepository
{
    public function saveUserProgramApplication(User $user, int $programId) : void
    {
        $updated = false;
        do {
            try {
                $this->getEntityManager()->transactional(function (EntityManager $em) use ($user, $programId) : void {
                    $program = $em->getRepository(Program::class)->find($programId, LockMode::PESSIMISTIC_WRITE);
                    assert($program instanceof Program);

                    $capacity = $program->getCapacity();
                    $occupancy = $program->getOccupancy();

                    $alternate = false;

                    if ($capacity !== null && $occupancy >= $capacity && !$program->getBlock()->isAlternatesAllowed()) {
                        throw new Full;
                    } else if ($capacity !== null && $occupancy >= $capacity) {
                        $alternate = true;
                    }

                    if ($this->findOneBy(['user' => $user, 'program' => $program, 'alternate' => false]) !== null) {
                        throw new AlredyRegistered;
                    }

                    if ($this->userAttendsSameBlockProgram($user, $program->getBlock())) {
                        throw new AlredyHasBlock;
                    }

                    if ($this->userAttendsOrAlternatesConflictingProgram($user, $program)) {
                        throw new HasSameTimeProgram;
                    }

                    //todo: ma program povolen?

                    $this->_em->persist(new ProgramApplication($user, $program, $alternate));

                    if (! $alternate) {
                        $program->setOccupancy($occupancy + 1);
                        $this->_em->persist($program);

                        foreach ($this->findUserAlternatesSameBlockPrograms($user, $program->getBlock()) as $programApplication) {
                            $this->_em->remove($programApplication);
                        }
                    }
                });

                $updated = true;
            } catch (Lock $e) {
                wait;
            }
        } while (! $updated);
    }

    public function removeUserProgramApplication(User $user, int $programId) : void
    {
        $updated = false;
        do {
            try {
                $this->getEntityManager()->transactional(function (EntityManager $em) use ($user, $programId) : void {
                    $program = $em->getRepository(Program::class)->find($programId, LockMode::PESSIMISTIC_WRITE);
                    assert($program instanceof Program);

                    $occupancy = $program->getOccupancy();

                    $programApplication = $this->findOneBy(['user' => $user, 'program' => $program]) !== null;
                    assert($programApplication instanceof ProgramApplication);

                    if ($programApplication === null) {
                        throw new NotRegistered;
                    }

                    $alternate = $programApplication->isAlternate();

                    $this->_em->remove($programApplication);

                    if (! $alternate) {
                        $program->setOccupancy($occupancy - 1);
                        $this->_em->persist($program);
                    }
                });

                $updated = true;
            } catch (Lock $e) {
                wait;
            }
        } while (! $updated);
    }

    /**
     * @return Collection<Program>
     */
    private function findUserAlternatesSameBlockPrograms(User $user, Block $block) : Collection
    {
        $result = $this->createQueryBuilder('pa')
            ->innerJoin('pa.program', 'p', 'WITH', 'b = :block')
            ->where('pa.user = :user')
            ->andWhere('pa.alternate = true')
            ->setParameter('user', $user)
            ->setParameter('block', $block)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    private function userAttendsSameBlockProgram(User $user, Block $block) : bool
    {
        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->innerJoin('pa.program', 'p', 'WITH', 'b = :block')
            ->where('pa.user = :user')
            ->andWhere('pa.alternate = false')
            ->setParameter('user', $user)
            ->setParameter('block', $block)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }

    private function userAttendsOrAlternatesConflictingProgram(User $user, Program $program) : bool
    {
        $start = $program->getStart();
        $end   = $program->getEnd();

        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->innerJoin('pa.program', 'p')
            ->where('pa.user = :user')
            ->andWhere('p != :program')
            ->andWhere($this->createQueryBuilder('p')->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->setParameter('user', $user)
            ->setParameter('program', $program)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }


}
