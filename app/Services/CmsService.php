<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Cms\Content;
use App\Model\Cms\Dto\PageDto;
use App\Model\Cms\Exceptions\PageException;
use App\Model\Cms\Page;
use App\Model\Cms\Repositories\ContentRepository;
use App\Model\Cms\Repositories\PageRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Throwable;

use function array_map;

/**
 * Služba pro správu stránek.
 */
class CmsService
{
    private Cache $pageCache;

    private Cache $menuCache;

    public function __construct(private PageRepository $pageRepository, private ContentRepository $contentRepository, Storage $storage)
    {
        $this->pageCache = new Cache($storage, 'Page');
        $this->menuCache = new Cache($storage, 'Menu');
    }

    /**
     * Uloží stránku.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function savePage(Page $page): void
    {
        $this->pageRepository->save($page);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Odstraní stránku.
     *
     * @throws PageException
     */
    public function removePage(Page $page): void
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
    public function sort(int $itemId, int $prevId, int $nextId): void
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
    public function findPublishedBySlugDto(string $slug): ?PageDto
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
    public function findPublishedOrderedByPositionDto(): array
    {
        $pagesDto = $this->menuCache->load(null);
        if ($pagesDto === null) {
            $pagesDto = array_map(
                static fn (Page $page) => $page->convertToDto(),
                $this->pageRepository->findPublishedOrderedByPosition()
            );
            $this->menuCache->save(null, $pagesDto);
        }

        return $pagesDto;
    }

    /**
     * Uloží obsah.
     */
    public function saveContent(Content $content): void
    {
        $this->contentRepository->save($content);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Odstraní obsah.
     */
    public function removeContent(Content $content): void
    {
        $this->contentRepository->remove($content);
        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }
}
