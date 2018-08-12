<?php
declare(strict_types=1);

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující tagy dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TagRepository extends EntityRepository
{
    /**
     * Vrátí tag podle id.
     * @param $id
     * @return Tag|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí tagy podle id.
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
     * Vrátí id tagů.
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
     * Vrátí všechny názvy tagů.
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
     * Vrátí názvy tagů, kromě tagu s id.
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
     * Uloží tag.
     * @param Tag $tag
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Tag $tag)
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    /**
     * Odstraní tag.
     * @param Tag $tag
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Tag $tag)
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    /**
     * Vrátí seznam tagů jako možnosti pro select.
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
