<?php

declare(strict_types=1);

namespace App\Model\Cms\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * Třída spravující tagy dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class TagRepository extends EntityRepository
{
    /**
     * Vrátí tag podle id.
     */
    public function findById(?int $id) : ?Tag
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí tagy podle id.
     *
     * @param int[] $ids
     *
     * @return Collection|Tag[]
     */
    public function findTagsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->matching($criteria);
    }

    /**
     * Vrátí id tagů.
     *
     * @param Collection|Tag[] $tags
     *
     * @return int[]
     */
    public function findTagsIds(Collection $tags) : array
    {
        return array_map(static function (Tag $o) {
            return $o->getId();
        }, $tags->toArray());
    }

    /**
     * Vrátí všechny názvy tagů.
     *
     * @return string[]
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrátí názvy tagů, kromě tagu s id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id) : array
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
     *
     * @throws ORMException
     */
    public function save(Tag $tag) : void
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    /**
     * Odstraní tag.
     *
     * @throws ORMException
     */
    public function remove(Tag $tag) : void
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    /**
     * Vrátí seznam tagů jako možnosti pro select.
     *
     * @return string[]
     */
    public function getTagsOptions() : array
    {
        $tags = $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($tags as $tag) {
            $options[$tag['id']] = $tag['name'];
        }

        return $options;
    }
}
