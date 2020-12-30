<?php

declare(strict_types=1);

namespace App\Model\Program\Repositories;

use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * Třída spravující přihlášky programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramApplicationRepository extends EntityRepository
{
    public function findByUserAndProgram(User $user, Program $program) : ?ProgramApplication
    {
        $result = $this->findOneBy(['user' => $user, 'program' => $program]);
        assert($result === null || $result instanceof ProgramApplication);
        return $result;
    }

    public function save(ProgramApplication $programApplication) : void
    {
        $this->_em->transactional(function () use ($programApplication) : void {
            $this->_em->createQuery('UPDATE App\Model\Program\Program p SET p.occupancy = p.occupancy + 1 WHERE p.id = :pid')
                ->setParameter('pid', $programApplication->getProgram()->getId())
                ->getResult();

            $this->_em->persist($programApplication);
        });
    }

//    public function saveMultiple(Collection $programApplications) : void
//    {
//        $this->_em->transactional(function () use ($programApplications) : void {
//            foreach ($programApplications as $programApplication) {
//                $this->save($programApplication);
//            }
//        });
//    }

    public function remove(ProgramApplication $programApplication) : void
    {
        $this->_em->transactional(function () use ($programApplication) : void {
            $this->_em->createQuery('UPDATE App\Model\Program\Program p SET p.occupancy = p.occupancy - 1 WHERE p.id = :pid')
                ->setParameter('pid', $programApplication->getProgram()->getId())
                ->getResult();

            $this->_em->remove($programApplication);
        });
    }
}
