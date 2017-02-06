<?php

namespace App\Model\CMS\Document;


use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;

class TagRepository extends EntityRepository
{
    public function findAllNames() {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function findOthersNames($id) {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    public function addTag($name) {
        $tag = new Tag();

        $tag->setName($name);

        $this->_em->persist($tag);
        $this->_em->flush();

        return $tag;
    }

    public function removeTag($id)
    {
        $tag = $this->find($id);
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    public function editTag($id, $name) {
        $tag = $this->find($id);

        $tag->setName($name);

        $this->_em->flush();

        return $tag;
    }

    public function findTagsOrderedByName() {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    public function findTagsByIds($ids) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }
}