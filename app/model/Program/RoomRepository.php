<?php

declare(strict_types=1);

namespace App\Model\Program;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use function array_map;

/**
 * Třída spravující místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoomRepository extends EntityRepository
{
    /**
     * Vrací místnost podle id.
     * @param $id
     */
    public function findById(int $id) : ?Room
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací názvy všech místností.
     * @return array
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací názvy místností, kromě místnosti s id.
     * @param $id
     * @return array
     */
    public function findOthersNames(int $id) : array
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrací místnosti podle id.
     * @param $ids
     * @return Collection
     */
    public function findRoomsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids));
        return $this->matching($criteria);
    }

    /**
     * Uloží místnost.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Room $room) : void
    {
        $this->_em->persist($room);
        $this->_em->flush();
    }

    /**
     * Odstraní místnost.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Room $room) : void
    {
        foreach ($room->getPrograms() as $program) {
            $program->setRoom(null);
            $this->_em->persist($program);
        }

        $this->_em->remove($room);
        $this->_em->flush();
    }

    /**
     * Je v místnosti jiný program ve stejnou dobu?
     */
    public function hasOverlappingProgram(Room $room, Program $program, \DateTime $start, \DateTime $end) : bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.id')
            ->join('r.programs', 'p')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('r.id = :rid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('rid', $room->getId());

        if ($program->getId()) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $program->getId());
        }

        return ! empty($qb->getQuery()->getResult());
    }
}
