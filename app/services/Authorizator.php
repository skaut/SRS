<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\PermissionRepository;
use App\Model\ACL\ResourceRepository;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;

/**
 * Služba nastavující role a oprávnění.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Authorizator extends Nette\Security\Permission
{
    public function __construct(
        RoleRepository $roleRepository,
        PermissionRepository $permissionRepository,
        ResourceRepository $resourceRepository
    ) {
        $this->addRole(Role::TEST); //role pouzivana pri testovani jine role

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
        } catch (TableNotFoundException $ex) {
            //prvni spusteni pred vytvorenim databaze
        }
    }
}
