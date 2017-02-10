<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class BlockRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Block|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return int
     */
    public function findLastId()
    {
        return $this->createQueryBuilder('b')
            ->select('MAX(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function findAllNames() {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param Block $block
     */
    public function save(Block $block)
    {
        $this->_em->persist($block);
        $this->_em->flush();
    }

    /**
     * @param Block $block
     */
    public function remove(Block $block)
    {
        $this->_em->remove($block);
        $this->_em->flush();
    }
}