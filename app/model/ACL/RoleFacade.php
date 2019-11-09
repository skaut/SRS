<?php
declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\EntityManagerDecorator;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use function array_map;

/**
 *
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class RoleFacade
{

	/** @var Cache */
	private $roleNamesCache;

	/** @var EntityManagerDecorator */
	private $em;

	/** @var RoleRepository */
	private $roleRepository;

	public function __construct(EntityManagerDecorator $em, IStorage $storage)
	{
		$this->em = $em;
		$this->roleNamesCache = new Cache($storage, 'RoleNames');
		$this->roleRepository = $em->getRepository(Role::class);
	}

	/**
	 * Vrací názvy všech rolí.
	 * @return string[]
	 * @throws \Throwable
	 */
	public function findAllNames(): array
	{
		$names = $this->roleNamesCache->load(null);
		if ($names === null) {
			$names = $this->roleRepository->createQueryBuilder('r')
				->select('r.name')
				->getQuery()
				->getScalarResult();
			$names = array_map('current', $names);
			$this->roleNamesCache->save(null, $names);
		}
		return $names;
	}

	/**
	 * Uloží roli.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function save(Role $role): void
	{
		$this->em->persist($role);
		$this->em->flush();

		$this->roleNamesCache->clean([Cache::NAMESPACES => ['RoleNames']]);
	}

	/**
	 * Odstraní roli.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function remove(Role $role): void
	{
		$this->em->remove($role);
		$this->em->flush();

		$this->roleNamesCache->clean([Cache::NAMESPACES => ['RoleNames']]);
	}
}
