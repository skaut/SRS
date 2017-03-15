<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\Criteria;
use Kdyby\Doctrine\EntityRepository;


class PermissionRepository extends EntityRepository
{
    /**
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
     * @param $permissionName
     * @param $resourceName
     * @return Permission
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