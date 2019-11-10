<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use function array_map;

/**
 * Třída spravující stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PageRepository extends EntityRepository
{
    /**
     * Vrací stránku podle id.
     */
    public function findById(?int $id) : ?Page
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrací viditelné stránky se zadaným slugem.
     * @throws \Throwable
     */
    public function findPublishedBySlug(string $slug) : ?Page
    {
        return $this->findOneBy(['public' => true, 'slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky, seřazené podle pozice.
     * @return Page[]
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
     * @return string[]
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
     * @return string[]
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
     * @param Collection|Page[] $pages
     * @return int[]
     */
    public function findPagesIds(Collection $pages) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky podle cest.
     * @param string[] $slugs
     * @return Collection|Page[]
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
     * @param Collection|Page[] $pages
     * @return string[]
     */
    public function findPagesSlugs(Collection $pages) : array
    {
        return array_map(function ($o) {
            return $o->getSlug();
        }, $pages->toArray());
    }

    /**
     * Vrací stránky jako možnosti pro select.
     * @return string[]
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
}
