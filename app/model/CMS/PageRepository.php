<?php

namespace App\Model\CMS;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PageRepository extends EntityRepository
{
    /**
     * Vrací stránku podle id.
     * @param $id
     * @return Page|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací stránku podle cesty.
     * @param $slug
     * @return Page|null
     */
    public function findBySlug($slug)
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky se zadaným slugem.
     * @param $slug
     * @return Page|null
     */
    public function findPublishedBySlug($slug)
    {
        return $this->findOneBy(['public' => TRUE, 'slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky, seřazené podle pozice.
     * @return array
     */
    public function findPublishedOrderedByPosition()
    {
        return $this->findBy(['public' => TRUE], ['position' => 'ASC']);
    }

    /**
     * Vrací poslední pozici stránky.
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastPosition()
    {
        return $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací všechny cesty.
     * @return array
     */
    public function findAllSlugs()
    {
        $slugs = $this->createQueryBuilder('p')
            ->select('p.slug')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $slugs);
    }

    /**
     * Vrací všechny cesty, kromě cesty stránky s id.
     * @param $id
     * @return array
     */
    public function findOthersSlugs($id)
    {
        $slugs = $this->createQueryBuilder('p')
            ->select('p.slug')
            ->where('p.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $slugs);
    }

    /**
     * Vrací id podle stránek.
     * @param $pages
     * @return array
     */
    public function findPagesIds($pages)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky podle cest.
     * @param $slugs
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findPagesBySlugs($slugs)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('slug', $slugs))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací cesty podle stránek.
     * @param $pages
     * @return array
     */
    public function findPagesSlugs($pages)
    {
        return array_map(function ($o) {
            return $o->getSlug();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky jako možnosti pro select.
     * @return array
     */
    public function getPagesOptions()
    {
        $pages = $this->createQueryBuilder('p')
            ->select('p.slug, p.name')
            ->orderBy('p.position')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($pages as $page) {
            $options[$page['slug']] = $page['name'];
        }
        return $options;
    }

    /**
     * Uloží stránku.
     * @param Page $page
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function save(Page $page)
    {
        if (!$page->getPosition())
            $page->setPosition($this->findLastPosition() + 1);

        $this->_em->persist($page);
        $this->_em->flush();
    }

    /**
     * Odstraní stránku.
     * @param Page $page
     * @throws \App\Model\Page\PageException
     */
    public function remove(Page $page)
    {
        foreach ($page->getContents() as $content)
            $this->_em->remove($content);

        $this->_em->remove($page);
        $this->_em->flush();
    }

    /**
     * Přesune stránku mezi stránky s id prevId a nextId.
     * @param $itemId
     * @param $prevId
     * @param $nextId
     */
    public function sort($itemId, $prevId, $nextId)
    {
        $item = $this->find($itemId);
        $prev = $prevId ? $this->find($prevId) : NULL;
        $next = $nextId ? $this->find($nextId) : NULL;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->_em->persist($t);
        }

        $itemsToMoveDown = $this->createQueryBuilder('i')
            ->where('i.position >= :position')
            ->setParameter('position', $next ? $next->getPosition() : PHP_INT_MAX)
            ->andWhere('i.position < :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveDown as $t) {
            $t->setPosition($t->getPosition() + 1);
            $this->_em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } else if ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}
