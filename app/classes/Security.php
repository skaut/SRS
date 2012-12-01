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
        $permissions = $em->getRepository('\SRS\Model\Acl\Permission')->findAll();

        foreach ($resources as $resource){
            $this->addResource($resource->name);
        }

        foreach ($roles as $role) {
            $this->addRole($role->name, $role->parent ? null : $role->parent->name);

            foreach ($role->permissions as $permission) {
                $this->allow($role->name, $permission->resource, $permission->name);
            }
        }

//        //roles
//        $this->addRole('guest');
//        $this->addRole('member', 'guest');
//        $this->addRole('editor', 'member');
//        $this->addRole('admin');
//
//        // resources
//        $this->addResource('ACL');
//        $this->addResource('');
//        $this->addResource('Admin:User');
//
//        // privileges
//        $this->allow('member', 'Admin:Default', Permission::ALL);
//        $this->allow('editor', 'Admin:Page',    Permission::ALL);
//        $this->allow('admin',  Permission::ALL, Permission::ALL);
    }

}

