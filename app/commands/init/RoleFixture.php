<?php

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Nette\Security\Passwords;
use App\Model\ACL\Role;
use App\Model\ACL\Resource;
use App\Model\ACL\Permission;

class RoleFixture extends AbstractFixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        $roles = array();
        foreach (Role::$roles as $role) {
            $roles[$role] = new Role($role);
        }

        $guest = $roles[Role::GUEST];
        $guest->registerable = false;
        $guest->syncedWithSkautIS = false;

        $unregistered = $roles[Role::UNREGISTERED];
        $unregistered->registerable = false;
        $unregistered->syncedWithSkautIS = false;

        $unapproved = $roles[Role::UNAPPROVED];
        $unapproved->registerable = false;
        $unapproved->syncedWithSkautIS = false;

        $attendee = $roles[Role::ATTENDEE];
        $attendee->approvedAfterRegistration = true;
        $attendee->permissions->add($this->getReference('program_choose_programs'));

        $serviceTeam = $roles[Role::SERVICE_TEAM];
        $serviceTeam->permissions->add($this->getReference('admin_access'));
        $serviceTeam->permissions->add($this->getReference('program_access'));

        $lector = $roles[Role::LECTOR];
        $lector->permissions->add($this->getReference('admin_access'));
        $lector->permissions->add($this->getReference('program_access'));
        $lector->permissions->add($this->getReference('program_manage_own_programs'));

        $organizer = $roles[Role::ORGANIZER];
        $organizer->permissions->add($this->getReference('admin_access'));
        $organizer->permissions->add($this->getReference('acl_manage'));
        $organizer->permissions->add($this->getReference('cms_manage'));
        $organizer->permissions->add($this->getReference('configuration_manage'));
        $organizer->permissions->add($this->getReference('program_access'));
        $organizer->permissions->add($this->getReference('program_manage_all_programs'));
        $organizer->permissions->add($this->getReference('program_manage_harmonogram'));
        $organizer->permissions->add($this->getReference('program_manage_rooms'));
        $organizer->permissions->add($this->getReference('program_manage_categories'));
        $organizer->permissions->add($this->getReference('program_choose_programs'));
        $organizer->permissions->add($this->getReference('evidence_manage'));
        $organizer->permissions->add($this->getReference('mailing_manage'));

        $admin = $roles[Role::ADMIN];
        $admin->registerable = false;
        $admin->permissions->add($this->getReference('admin_access'));
        $admin->permissions->add($this->getReference('acl_manage'));
        $admin->permissions->add($this->getReference('cms_manage'));
        $admin->permissions->add($this->getReference('configuration_manage'));
        $admin->permissions->add($this->getReference('program_access'));
        $admin->permissions->add($this->getReference('program_manage_all_programs'));
        $admin->permissions->add($this->getReference('program_manage_harmonogram'));
        $admin->permissions->add($this->getReference('program_manage_rooms'));
        $admin->permissions->add($this->getReference('program_manage_categories'));
        $admin->permissions->add($this->getReference('program_choose_programs'));
        $admin->permissions->add($this->getReference('evidence_manage'));
        $admin->permissions->add($this->getReference('mailing_manage'));

        foreach ($roles as $key => $value) {
            $manager->persist($value);
        }
        $manager->flush();
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('PermissionFixture');
    }
}