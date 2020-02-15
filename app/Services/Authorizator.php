<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Role;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;
use Throwable;

/**
 * Služba nastavující role a oprávnění.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Authorizator extends Nette\Security\Permission
{
    /**
     * @throws Throwable
     */
    public function __construct(AclService $aclService)
    {
        $this->addRole(Role::TEST); //role pouzivana pri testovani jine role

        try {
            foreach ($aclService->findAllResourceNames() as $resourceName) {
                $this->addResource($resourceName);
            }

            foreach ($aclService->findAllRoleNames() as $roleName) {
                $this->addRole($roleName);
            }

            foreach ($aclService->findAllPermissionNames() as $permission) {
                $this->allow($permission['roleName'], $permission['resourceName'], $permission['name']);
            }
        } catch (TableNotFoundException $ex) {
            //prvni spusteni pred vytvorenim databaze
        }
    }
}
