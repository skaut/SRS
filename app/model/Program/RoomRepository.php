<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class RoomRepository extends EntityRepository
{
    public function findAllNames() {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function addRoom($name) {
        $room = new Room();

        $room->setName($name);

        $this->_em->persist($room);
        $this->_em->flush();

        return $room;
    }

    public function removeRoom($id)
    {
        $room = $this->find($id);
        $this->_em->remove($room);
        $this->_em->flush();
    }

    public function editRoom($id, $name) {
        $room = $this->find($id);

        $room->setName($name);

        $this->_em->flush();

        return $room;
    }

    public function findRoomsOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }
}