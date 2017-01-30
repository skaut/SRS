<?php

namespace App\Services;

use App\Model\ACL\ResourceRepository;
use App\Model\ACL\RoleRepository;
use Nette;
use Nette\Security\privilege;
use Nette\Security\role;

/**
 * Class Authorizator
 * @package App\Services
 */
class Authorizator extends Nette\Security\Permission
{
    public function __construct(RoleRepository $roleRepository, ResourceRepository $resourceRepository)
    {
        foreach ($resourceRepository->findAll() as $resource) {
            $this->addResource($resource->getName());
        }

        foreach ($roleRepository->findAll() as $role) {
            $this->addRole($role->getName());

            foreach ($role->getPermissions() as $permission) {
                $this->allow($role->getName(), $permission->getResource()->getName(), $permission->getName());
            }
        }
    }
}