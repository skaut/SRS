<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Model\EntityManagerDecorator;
use App\Model\CMS\Content\Content;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

/**
 * Třída spravující obsahy webu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContentFacade
{

	/** @var EntityManagerDecorator */
	private $em;

	/** @var Cache */
	private $pageCache;

	/** @var Cache */
	private $menuCache;

	public function __construct(EntityManagerDecorator $em, IStorage $storage)
	{
		$this->em = $em;
		$this->pageCache = new Cache($storage, 'Page');
		$this->menuCache = new Cache($storage, 'Menu');
	}

	/**
	 * Uloží obsah.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function save(Content $content): void
	{
		$this->em->persist($content);
		$this->em->flush();

		$this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
		$this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
	}

	/**
	 * Odstraní obsah.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function remove(Content $content): void
	{
		$this->em->remove($content);
		$this->em->flush();

		$this->pageCache->clean([Cache::NAMESPACES => ['Page']]);
		$this->menuCache->clean([Cache::NAMESPACES => ['Menu']]);
	}
}
