<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

/**
 * Třída spravující obsahy webu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContentRepository extends EntityRepository
{
    /** @var Cache */
    private $pageCache;

    /** @var Cache */
    private $menuCache;


    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, IStorage $storage)
    {
        parent::__construct($em, $class);
        $this->pageCache = new Cache($storage, 'Page');
        $this->menuCache = new Cache($storage, 'Menu');
    }

    /**
     * Uloží obsah.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Content $content) : void
    {
        $this->_em->persist($content);
        $this->_em->flush();

        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }

    /**
     * Odstraní obsah.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Content $content) : void
    {
        $this->_em->remove($content);
        $this->_em->flush();

        $this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
        $this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
    }
}
