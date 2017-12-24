<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující oprávnění.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PermissionRepository extends EntityRepository
{
    /**
     * Vrací názvy všech oprávnění.
     * @return array
     */
    public function findAllNames()
    {
        return $this->createQueryBuilder('p')
            ->select('p.name')
            ->addSelect('role.name AS roleName')->join('p.roles', 'role')
            ->addSelect('resource.name AS resourceName')->join('p.resource', 'resource')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vrací oprávnění podle id.
     * @param $ids
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findPermissionsByIds($ids)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrací id oprávnění.
     * @param $permissions
     * @return array
     */
    public function findPermissionsIds($permissions)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $permissions->toArray());
    }

    /**
     * Vrací oprávnění podle názvu oprávnění a prostředku.
     * @param $permissionName
     * @param $resourceName
     * @return Permission
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByPermissionAndResourceName($permissionName, $resourceName)
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
