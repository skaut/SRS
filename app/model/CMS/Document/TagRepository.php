<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


class TagRepository extends EntityRepository
{
    /**
     * @param $id
     * @return Tag|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findTagsByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * @param $tags
     * @return array
     */
    public function findTagsIds($tags)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $tags->toArray());
    }

    /**
     * @return array
     */
    public function findAllNames()
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param $id
     * @return array
     */
    public function findOthersNames($id)
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * @param Tag $tag
     */
    public function save(Tag $tag)
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    /**
     * @param Tag $tag
     */
    public function remove(Tag $tag)
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    /**
     * @return array
     */
    public function getTagsOptions()
    {
        $tags = $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($tags as $tag)
            $options[$tag['id']] = $tag['name'];
        return $options;
    }
}