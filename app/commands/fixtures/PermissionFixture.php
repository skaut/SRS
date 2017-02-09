<?php

namespace App\Commands\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\Model\ACL\Permission;

class PermissionFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $permissions = [];
        $permissions['admin_access'] = new Permission(Permission::ACCESS, $this->getReference('resource_admin'));
        $permissions['acl_manage'] = new Permission(Permission::MANAGE, $this->getReference('resource_acl'));
        $permissions['cms_manage'] = new Permission(Permission::MANAGE, $this->getReference('resource_cms'));
        $permissions['configuration_manage'] = new Permission(Permission::MANAGE, $this->getReference('resource_configuration'));
        $permissions['program_access'] = new Permission(Permission::ACCESS, $this->getReference('resource_program'));
        $permissions['program_manage_own_programs'] = new Permission(Permission::MANAGE_OWN_PROGRAMS, $this->getReference('resource_program'));
        $permissions['program_manage_all_programs'] = new Permission(Permission::MANAGE_ALL_PROGRAMS, $this->getReference('resource_program'));
        $permissions['program_manage_schedule'] = new Permission(Permission::MANAGE_SCHEDULE, $this->getReference('resource_program'));
        $permissions['program_manage_rooms'] = new Permission(Permission::MANAGE_ROOMS, $this->getReference('resource_program'));
        $permissions['program_manage_categories'] = new Permission(Permission::MANAGE_CATEGORIES, $this->getReference('resource_program'));
        $permissions['program_choose_programs'] = new Permission(Permission::CHOOSE_PROGRAMS, $this->getReference('resource_program'));
        $permissions['users_manage'] = new Permission(Permission::MANAGE, $this->getReference('resource_users'));
        $permissions['mailing_manage'] = new Permission(Permission::MANAGE, $this->getReference('resource_mailing'));

        foreach ($permissions as $permission) {
            $manager->persist($permission);
        }
        $manager->flush();

        foreach ($permissions as $key => $value) {
            $this->addReference($key, $value);
        }
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('App\Commands\Fixtures\ResourceFixture');
    }
}