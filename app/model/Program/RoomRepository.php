<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class RoomRepository extends EntityRepository
{
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

    public function isNameUnique($name, $id = null) {
        $tag = $this->findOneBy(['name' => $name]);
        if ($tag) {
            if ($id == $tag->getId())
                return true;
            return false;
        }
        return true;
    }

    public function findRoomsOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }
}