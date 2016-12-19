<?php

namespace App\Services;

use Nette;
use Nette\Security\privilege;
use Nette\Security\role;

class Authorizator extends Nette\Security\Permission
{
    /**
     * @var \App\Model\ACL\RoleRepository
     */
    protected $roleRepository;

    /**
     * @var \App\Model\ACL\ResourceRepository
     */
    protected $resourceRepository;

    /**
     * Authorizator constructor.
     * @param \App\Model\ACL\RoleRepository $roleRepository
     * @param \App\Model\ACL\ResourceRepository $resourceRepository
     */
    public function __construct(\App\Model\ACL\RoleRepository $roleRepository, \App\Model\ACL\ResourceRepository $resourceRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->resourceRepository = $resourceRepository;

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