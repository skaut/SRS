<?php

declare(strict_types=1);

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

/**
 * Třída spravující oprávnění.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PermissionRepository extends EntityRepository
{
    /** @var Cache */
    private $permissionNamesCache;


    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, IStorage $storage)
    {
        parent::__construct($em, $class);
        $this->permissionNamesCache = new Cache($storage, 'PermissionNames');
    }

    /**
     * Vrací názvy všech oprávnění.
     * @return Collection|string[]
     */
    public function findAllNames() : Collection
    {
        $names = $this->permissionNamesCache->load(null);
        if ($names === null) {
            $names = $result = $this->createQueryBuilder('p')
                ->select('p.name')
                ->addSelect('role.name AS roleName')->join('p.roles', 'role')
                ->addSelect('resource.name AS resourceName')->join('p.resource', 'resource')
                ->getQuery()
                ->getResult();
            $this->permissionNamesCache->save(null, $names);
        }
        return new ArrayCollection($names);
    }

    /**
     * Vrací oprávnění podle id.
     * @param int[] $ids
     * @return Collection|Permission[]
     */
    public function findPermissionsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací id oprávnění.
     * @param Collection|Permission[] $permissions
     * @return int[]
     */
    public function findPermissionsIds(Collection $permissions) : array
    {
        return $permissions->map(function (Permission $permission) {
            return $permission->getId();
        })->toArray();
    }

    /**
     * Vrací oprávnění podle názvu oprávnění a prostředku.
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByPermissionAndResourceName(string $permissionName, string $resourceName) : ?Permission
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.resource', 'r')
            ->where('p.name = :permissionName')->setParameter('permissionName', $permissionName)
            ->andWhere('r.name = :resourceName')->setParameter('resourceName', $resourceName)
            ->getQuery()
            ->getSingleResult();
    }
}
