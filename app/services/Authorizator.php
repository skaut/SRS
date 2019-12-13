<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Role;
use App\Services\ACLService;
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
     * Authorizator constructor.
     * @throws Throwable
     */
    public function __construct(ACLService $ACLService)
    {
        $this->addRole(Role::TEST); //role pouzivana pri testovani jine role

        try {
            foreach ($ACLService->findAllResourceNames() as $resourceName) {
                $this->addResource($resourceName);
            }
            foreach ($ACLService->findAllRoleNames() as $roleName) {
                $this->addRole($roleName);
            }
            foreach ($ACLService->findAllPermissionNames() as $permission) {
                $this->allow($permission['roleName'], $permission['resourceName'], $permission['name']);
            }
        } catch (TableNotFoundException $ex) {
            //prvni spusteni pred vytvorenim databaze
        }
    }
}
