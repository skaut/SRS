<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class BlockRepository extends EntityRepository
{
    public function findBlockById($id) {
        return $this->find($id);
    }

    public function findAllNames() {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('b')
            ->select('b.name')
            ->where('b.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function addBlock($name, $category, $lector, $duration, $capacity, $mandatory, $perex, $description, $tools) {
        $block = new Block();

        $block->setName($name);
        $block->setCategory($category);
        $block->setLector($lector);
        $block->setDuration($duration);
        $block->setCapacity($capacity);
        $block->setMandatory($mandatory);
        $block->setPerex($perex);
        $block->setDescription($description);
        $block->setTools($tools);

        $this->_em->persist($block);
        $this->_em->flush();

        return $block;
    }

    public function editBlock($id, $name, $category, $lector, $duration, $capacity, $mandatory, $perex, $description, $tools) {
        $block = $this->find($id);

        $block->setName($name);
        $block->setCategory($category);
        $block->setLector($lector);
        $block->setDuration($duration);
        $block->setCapacity($capacity);
        $block->setMandatory($mandatory);
        $block->setPerex($perex);
        $block->setDescription($description);
        $block->setTools($tools);

        $this->_em->flush();

        return $block;
    }

    public function setBlockMandatory($id, $mandatory) {
        $block = $this->find($id);
        $block->setMandatory($mandatory);
        $this->_em->flush();
        return $block;
    }

    public function removeBlock($id)
    {
        $block = $this->find($id);
        $this->_em->remove($block);
        $this->_em->flush();
    }
}