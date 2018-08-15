<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\Page\PageException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use const PHP_INT_MAX;
use function array_map;

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
     */
    public function findById(int $id) : ?Page
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací stránku podle cesty.
     * @param $slug
     */
    public function findBySlug(string $slug) : ?Page
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky se zadaným slugem.
     * @param $slug
     */
    public function findPublishedBySlug(string $slug) : ?Page
    {
        return $this->findOneBy(['public' => true, 'slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky, seřazené podle pozice.
     * @return array
     */
    public function findPublishedOrderedByPosition() : array
    {
        return $this->findBy(['public' => true], ['position' => 'ASC']);
    }

    /**
     * Vrací poslední pozici stránky.
     * @throws NonUniqueResultException
     */
    public function findLastPosition() : int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací všechny cesty.
     * @return array
     */
    public function findAllSlugs() : array
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
    public function findOthersSlugs(int $id) : array
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
    public function findPagesIds(Collection $pages) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky podle cest.
     * @param $slugs
     * @return Collection
     */
    public function findPagesBySlugs(array $slugs) : Collection
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
    public function findPagesSlugs(Collection $pages) : array
    {
        return array_map(function ($o) {
            return $o->getSlug();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky jako možnosti pro select.
     * @return array
     */
    public function getPagesOptions() : array
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
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Page $page) : void
    {
        if (! $page->getPosition()) {
            $page->setPosition($this->findLastPosition() + 1);
        }

        $this->_em->persist($page);
        $this->_em->flush();
    }

    /**
     * Odstraní stránku.
     * @throws PageException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Page $page) : void
    {
        foreach ($page->getContents() as $content) {
            $this->_em->remove($content);
        }

        $this->_em->remove($page);
        $this->_em->flush();
    }

    /**
     * Přesune stránku mezi stránky s id prevId a nextId.
     * @param $itemId
     * @param $prevId
     * @param $nextId
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function sort(int $itemId, int $prevId, int $nextId) : void
    {
        $item = $this->find($itemId);
        $prev = $prevId ? $this->find($prevId) : null;
        $next = $nextId ? $this->find($nextId) : null;

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
        } elseif ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->_em->persist($item);
        $this->_em->flush();
    }
}
