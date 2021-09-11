<?php

declare(strict_types=1);

namespace App\Model\Acl\Repositories;

use App\Model\Acl\Permission;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Třída spravující oprávnění.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PermissionRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Permission::class);
    }

    /**
     * Vrací oprávnění podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Permission>
     */
    public function findPermissionsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrací id oprávnění.
     *
     * @param Collection<Permission> $permissions
     *
     * @return int[]
     */
    public function findPermissionsIds(Collection $permissions): array
    {
        return $permissions->map(static function (Permission $permission) {
            return $permission->getId();
        })->toArray();
    }

    /**
     * Vrací oprávnění podle názvu oprávnění a prostředku.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByPermissionAndResourceName(string $permissionName, string $resourceName): ?Permission
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
