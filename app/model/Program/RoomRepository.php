<?php

declare(strict_types=1);

namespace App\Model\Program;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class RoomRepository extends EntityRepository
{
    /**
     * Vrací místnost podle id.
     */
    public function findById(?int $id) : ?Room
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací názvy všech místností.
     * @return string[]
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
     * @return string[]
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
     * @param int[] $ids
     * @return Collection|Room[]
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
     */
    public function save(Room $room) : void
    {
        $this->_em->persist($room);
        $this->_em->flush();
    }

    /**
     * Odstraní místnost.
     * @throws ORMException
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
    public function hasOverlappingProgram(Room $room, ?int $programId, DateTime $start, DateTime $end) : bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.id')
            ->join('r.programs', 'p')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder('r')->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('r.id = :rid')
            ->andWhere('p.id != :pid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('rid', $room->getId())
            ->setParameter('pid', $programId);

        return ! empty($qb->getQuery()->getResult());
    }
}
