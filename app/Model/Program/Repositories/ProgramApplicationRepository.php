<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Program\Block;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
use App\Model\Program\Exceptions\UserAttendsConflictingProgramException;
use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Throwable;
use function assert;

/**
 * Třída spravující přihlášky programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramApplicationRepository extends EntityRepository
{
    public function findUserProgramApplication(User $user, Program $program) : ?ProgramApplication
    {
        return $this->findOneBy(['user' => $user, 'program' => $program]);
    }

    /**
     * @throws Throwable
     */
    public function saveUserProgramApplication(User $user, Program $program) : ?ProgramApplication
    {
        $programApplication = null;

        $this->getEntityManager()->transactional(function (EntityManager $em) use ($user, $program, $programApplication) : void {
            $program = $em->getRepository(Program::class)->find($program->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $capacity  = $program->getCapacity();
            $occupancy = $program->getOccupancy();

            $alternate = false;

            if ($capacity !== null && $occupancy >= $capacity /*&& ! $program->getBlock()->isAlternatesAllowed()*/) {
                throw new ProgramCapacityOccupiedException();
            } elseif ($capacity !== null && $occupancy >= $capacity) {
                $alternate = true;
            }

            if ($this->findUserProgramApplication($user, $program) !== null) {
                throw new UserAlreadyAttendsProgramException();
            }

            if ($this->userAttendsSameBlockProgram($user, $program->getBlock())) {
                throw new UserAlreadyAttendsBlockException();
            }

            if ($this->userAttendsOrAlternatesConflictingProgram($user, $program)) {
                throw new UserAttendsConflictingProgramException();
            }

            $programApplication = new ProgramApplication($user, $program, $alternate);
            $this->_em->persist($programApplication);

            if (! $alternate) {
                $program->setOccupancy($occupancy + 1);
                $this->_em->persist($program);

                $programRepository = $this->_em->getRepository('App\Model\Program\Program');
                assert($programRepository instanceof ProgramRepository);
                foreach ($programRepository->findUserAlternatesAndBlock($user, $program->getBlock()) as $pa) {
                    $this->_em->remove($pa);
                }
            }
        });

        return $programApplication;
    }

    /**
     * @throws Throwable
     */
    public function removeUserProgramApplication(User $user, Program $program) : void
    {
        $this->getEntityManager()->transactional(function (EntityManager $em) use ($user, $program) : void {
            $program = $em->getRepository(Program::class)->find($program->getId(), LockMode::PESSIMISTIC_WRITE);
            assert($program instanceof Program);

            $occupancy = $program->getOccupancy();

            $programApplication = $this->findUserProgramApplication($user, $program);

            if ($programApplication === null) {
                throw new UserNotAttendsProgramException();
            }

            $alternate = $programApplication->isAlternate();

            $this->_em->remove($programApplication);

            if (! $alternate) {
                $program->setOccupancy($occupancy - 1);
                $this->_em->persist($program);
            }
        });
    }

    private function userAttendsSameBlockProgram(User $user, Block $block) : bool
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

    private function userAttendsOrAlternatesConflictingProgram(User $user, Program $program) : bool
    {
        $start = $program->getStart();
        $end   = $program->getEnd();

        $result = $this->createQueryBuilder('pa')
            ->select('count(pa)')
            ->join('pa.program', 'p')
            ->join('p.block', 'b')
            ->where('pa.user = :user')
            ->andWhere('p != :program')
            ->andWhere('p.start < :end')
            ->andWhere("DATE_ADD(p.start, (b.duration * 60), 'second') > :start")
            ->setParameter('user', $user)
            ->setParameter('program', $program)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== 0;
    }
}
