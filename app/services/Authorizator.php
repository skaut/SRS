<?php

namespace App\Services;

use App\Model\ACL\PermissionRepository;
use App\Model\ACL\ResourceRepository;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;

/**
 * Class Authorizator
 * @package App\Services
 */
class Authorizator extends Nette\Security\Permission
{
    public function __construct(RoleRepository $roleRepository, PermissionRepository $permissionRepository,
                                ResourceRepository $resourceRepository)
    {
        //role pouzivana pri testovani jine role
        $this->addRole(Role::TEST);

        try {
            foreach ($resourceRepository->findAllNames() as $resourceName) {
                $this->addResource($resourceName);
            }
            foreach ($roleRepository->findAllNames() as $roleName) {
                $this->addRole($roleName);
            }
            foreach ($permissionRepository->findAllNames() as $permission) {
                $this->allow($permission['roleName'], $permission['resourceName'], $permission['name']);
            }
        } catch (TableNotFoundException $ex) { } //prvni spusteni pred vytvorenim databaze
    }
}