<?php

namespace App\Services;

use Nette;
use Nette\Security\privilege;
use Nette\Security\role;

/**
 * Class Authorizator
 * @package App\Services
 */
class Authorizator extends Nette\Security\Permission
{
    /**
     * Authorizator constructor.
     * @param \Kdyby\Doctrine\EntityManager $em
     */
    public function __construct(\Kdyby\Doctrine\EntityManager $em)
    {
        $roleRepository = $em->getRepository(\App\Model\ACL\Role::class);
        $resourceRepository = $em->getRepository(\App\Model\ACL\Resource::class);

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