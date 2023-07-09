<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\Exceptions\PageException;
use App\Model\Cms\Page;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Throwable;

use function array_map;

use const PHP_INT_MAX;

/**
 * Třída spravující stránky.
 */
class PageRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Page::class);
    }

    /**
     * Vrací stránku podle id.
     */
    public function findById(int|null $id): Page|null
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrací viditelné stránky se zadaným slugem.
     *
     * @throws Throwable
     */
    public function findPublishedBySlug(string $slug): Page|null
    {
        return $this->getRepository()->findOneBy(['public' => true, 'slug' => $slug]);
    }

    /**
     * Vrací viditelné stránky, seřazené podle pozice.
     *
     * @return Page[]
     */
    public function findPublishedOrderedByPosition(): array
    {
        return $this->getRepository()->findBy(['public' => true], ['position' => 'ASC']);
    }

    /**
     * Vrací poslední pozici stránky.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findLastPosition(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vrací všechny cesty.
     *
     * @return string[]
     */
    public function findAllSlugs(): array
    {
        $slugs = $this->createQueryBuilder('p')
            ->select('p.slug')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $slugs);
    }

    /**
     * Vrací všechny cesty, kromě cesty stránky s id.
     *
     * @return string[]
     */
    public function findOthersSlugs(int $id): array
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
     *
     * @param Collection<int, Page> $pages
     *
     * @return int[]
     */
    public function findPagesIds(Collection $pages): array
    {
        return array_map(static fn (Page $o) => $o->getId(), $pages->toArray());
    }

    /**
     * Vrací stránky podle cest.
     *
     * @param string[] $slugs
     *
     * @return Collection<int, Page>
     */
    public function findPagesBySlugs(array $slugs): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('slug', $slugs))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací cesty podle stránek.
     *
     * @param Collection<int, Page> $pages
     *
     * @return string[]
     */
    public function findPagesSlugs(Collection $pages): array
    {
        return array_map(static fn (Page $o) => $o->getSlug(), $pages->toArray());
    }

    /**
     * Vrací stránky jako možnosti pro select.
     *
     * @return string[]
     */
    public function getPagesOptions(): array
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
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function save(Page $page): void
    {
        if (! $page->getPosition()) {
            $page->setPosition($this->findLastPosition() + 1);
        }

        $this->em->persist($page);
        $this->em->flush();
    }

    /**
     * Odstraní stránku.
     *
     * @throws PageException
     */
    public function remove(Page $page): void
    {
        foreach ($page->getContents() as $content) {
            $this->em->remove($content);
        }

        $this->em->remove($page);
        $this->em->flush();
    }

    /**
     * Přesune stránku mezi stránky s id prevId a nextId.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function sort(int $itemId, int $prevId, int $nextId): void
    {
        $item = $this->getRepository()->find($itemId);
        $prev = $prevId ? $this->getRepository()->find($prevId) : null;
        $next = $nextId ? $this->getRepository()->find($nextId) : null;

        $itemsToMoveUp = $this->createQueryBuilder('i')
            ->where('i.position <= :position')
            ->setParameter('position', $prev ? $prev->getPosition() : 0)
            ->andWhere('i.position > :position2')
            ->setParameter('position2', $item->getPosition())
            ->getQuery()
            ->getResult();

        foreach ($itemsToMoveUp as $t) {
            $t->setPosition($t->getPosition() - 1);
            $this->em->persist($t);
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
            $this->em->persist($t);
        }

        if ($prev) {
            $item->setPosition($prev->getPosition() + 1);
        } elseif ($next) {
            $item->setPosition($next->getPosition() - 1);
        } else {
            $item->setPosition(1);
        }

        $this->em->persist($item);
        $this->em->flush();
    }
}
