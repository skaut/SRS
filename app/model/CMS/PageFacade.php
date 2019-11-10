<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\EntityManagerDecorator;
use App\Model\Page\PageException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use const PHP_INT_MAX;
use function array_map;

/**
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PageFacade
{
    /** @var Cache */
    private $pageCache;

    /** @var Cache */
    private $menuCache;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var PageRepository */
    private $pageRepository;

    public function __construct(EntityManagerDecorator $em, PageRepository $pageRepository, IStorage $storage)
    {
        $this->em             = $em;
        $this->pageRepository = $pageRepository;
        $this->pageCache      = new Cache($storage, 'Page');
        $this->menuCache      = new Cache($storage, 'Menu');
    }

    /**
     * Vrací DTO viditelné stránky se zadaným slugem.
     *
     * @throws \Throwable
     */
    public function findPublishedBySlugDTO(string $slug) : ?PageDTO
    {
        $pageDTO = $this->pageCache->load($slug);
        if ($pageDTO === null) {
            $page = $this->pageRepository->findPublishedBySlug($slug);
            if ($page !== null) {
                $pageDTO = $page->convertToDTO();
                $this->pageCache->save($slug, $pageDTO);
            }
        }
        return $pageDTO;
    }

    /**
     * Vrací DTO viditelných stránek, seřazená podle pozice.
     *
     * @return PageDTO[]
     * @throws \Throwable
     */
    public function findPublishedOrderedByPositionDTO() : array
    {
        $pagesDTO = $this->menuCache->load(null);
        if ($pagesDTO === null) {
            $pagesDTO = array_map(
                function (Page $page) {
                        return $page->convertToDTO();
                },
                $this->pageRepository->findPublishedOrderedByPosition()
            );
            $this->menuCache->save(null, $pagesDTO);
        }
        return $pagesDTO;
    }

    /**
     * Uloží stránku.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Page $page) : void
    {
        if (! $page->getPosition()) {
            $page->setPosition($this->findLastPosition() + 1);
        }

        $this->em->persist($page);
        $this->em->flush();

        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Odstraní stránku.
     *
     * @throws PageException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Page $page) : void
    {
        foreach ($page->getContents() as $content) {
            $this->em->remove($content);
        }

        $this->em->remove($page);
        $this->em->flush();

        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Přesune stránku mezi stránky s id prevId a nextId.
     *
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

        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }
}
