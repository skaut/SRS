<?php

namespace App\Model\ACL;

use Kdyby\Doctrine\EntityRepository;

class PermissionRepository extends EntityRepository
{
    public function findPermissionsNames() {
        return $this->createQueryBuilder('p')
            ->select('p.name')
            ->addSelect('role.name AS roleName')->join('p.roles', 'role')
            ->addSelect('resource.name AS resourceName')->join('p.resource', 'resource')
            ->getQuery()->execute();
    }
}