<?php

namespace SRS\Security;

use \Nette\Security\Permission;

class Acl extends Permission
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct($em)
    {
        $roles = $em->getRepository('\SRS\Model\Acl\Role')->findAll();
        $resources = $em->getRepository('\SRS\Model\Acl\Resource')->findAll();

        foreach ($resources as $resource){
            $this->addResource($resource->name);
        }

        foreach ($roles as $role) {
            //$this->addRole($role->name, isset($role->parent) ? null : $role->parent->name);
            $this->addRole($role->name);

            foreach ($role->permissions as $permission) {
                $this->allow($role->name, $permission->resource->name, $permission->name);
            }
        }
    }

}

