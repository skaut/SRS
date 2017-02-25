<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class RoomRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Room|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return array
     */
    public function findAllNames() {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->where('r.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param Room $room
     */
    public function save(Room $room)
    {
        $this->_em->persist($room);
        $this->_em->flush();
    }

    /**
     * @param Room $room
     */
    public function remove(Room $room)
    {
        foreach ($room->getPrograms() as $program) {
            $program->setRoom(null);
            $this->_em->persist($program);
        }

        $this->_em->remove($room);
        $this->_em->flush();
    }
}