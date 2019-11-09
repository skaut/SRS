<?php
declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\EntityManagerDecorator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use App\Model\EntityRepository;
use function array_map;

/**
 * Třída spravující prostředky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ResourceFacade
{

	/** @var EntityManagerDecorator */
	private $em;

	/** @var EntityRepository */
	private $resourceRepository;

	/** @var Cache */
	private $resourceNamesCache;

	public function __construct(EntityManagerDecorator $em, IStorage $storage)
	{
		$this->em = $em;
		$this->resourceRepository = $em->getRepository(Resource::class);
		$this->resourceNamesCache = new Cache($storage, 'ResourceNames');
	}

	/**
	 * Vrací názvy všech prostředků.
	 * @return string[]
	 * @throws \Throwable
	 */
	public function findAllNames(): array
	{
		$names = $this->resourceNamesCache->load(null);
		if ($names === null) {
			$names = $this->resourceRepository->createQueryBuilder('r')
				->select('r.name')
				->getQuery()
				->getScalarResult();
			$names = array_map('current', $names);
			$this->resourceNamesCache->save(null, $names);
		}
		return $names;
	}
}
