<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentRepository;
use App\Model\CMS\Page;
use App\Model\CMS\PageDto;
use App\Model\CMS\PageRepository;
use App\Model\Page\PageException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Throwable;
use function array_map;

/**
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CmsService
{
    /** @var Cache */
    private $pageCache;

    /** @var Cache */
    private $menuCache;

    /** @var PageRepository */
    private $pageRepository;

    /** @var ContentRepository */
    private $contentRepository;

    public function __construct(PageRepository $pageRepository, ContentRepository $contentRepository, IStorage $storage)
    {
        $this->pageRepository    = $pageRepository;
        $this->contentRepository = $contentRepository;
        $this->pageCache         = new Cache($storage, 'Page');
        $this->menuCache         = new Cache($storage, 'Menu');
    }

    /**
     * Uloží stránku.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function savePage(Page $page) : void
    {
        $this->pageRepository->save($page);
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
    public function removePage(Page $page) : void
    {
        $this->pageRepository->remove($page);
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
        $this->pageRepository->sort($itemId, $prevId, $nextId);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Vrací DTO viditelné stránky se zadaným slugem.
     *
     * @throws Throwable
     */
    public function findPublishedBySlugDto(string $slug) : ?PageDto
    {
        $pageDto = $this->pageCache->load($slug);
        if ($pageDto === null) {
            $page = $this->pageRepository->findPublishedBySlug($slug);
            if ($page !== null) {
                $pageDto = $page->convertToDto();
                $this->pageCache->save($slug, $pageDto);
            }
        }

        return $pageDto;
    }

    /**
     * Vrací DTO viditelných stránek, seřazená podle pozice.
     *
     * @return PageDto[]
     *
     * @throws Throwable
     */
    public function findPublishedOrderedByPositionDto() : array
    {
        $pagesDto = $this->menuCache->load(null);
        if ($pagesDto === null) {
            $pagesDto = array_map(
                static function (Page $page) {
                    return $page->convertToDto();
                },
                $this->pageRepository->findPublishedOrderedByPosition()
            );
            $this->menuCache->save(null, $pagesDto);
        }

        return $pagesDto;
    }

    /**
     * Uloží obsah.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveContent(Content $content) : void
    {
        $this->contentRepository->save($content);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Odstraní obsah.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeContent(Content $content) : void
    {
        $this->contentRepository->remove($content);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }
}
