<?php

namespace App\Commands\Fixtures;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Kdyby\Translation\Translator;
use App\Model\ACL\Role;

class RoleFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * RoleFixture constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function load(ObjectManager $manager)
    {
        $roles = array();
        foreach (Role::$roles as $role) {
            $roles[$role] = new Role($this->translator->translate('common.role.' . $role));
            $roles[$role]->setUntranslatedName($role);
        }

        $guest = $roles[Role::GUEST];
        $guest->setRegisterable(false);
        $guest->setSyncedWithSkautIS(false);

        $unregistered = $roles[Role::UNREGISTERED];
        $unregistered->setRegisterable(false);
        $unregistered->setSyncedWithSkautIS(false);

        $unapproved = $roles[Role::UNAPPROVED];
        $unapproved->setRegisterable(false);
        $unapproved->setSyncedWithSkautIS(false);

        $attendee = $roles[Role::ATTENDEE];
        $attendee->setApprovedAfterRegistration(true);
        $attendee->addPermission($this->getReference('program_choose_programs'));

        $serviceTeam = $roles[Role::SERVICE_TEAM];
        $serviceTeam->addPermission($this->getReference('admin_access'));
        $serviceTeam->addPermission($this->getReference('program_access'));

        $lector = $roles[Role::LECTOR];
        $lector->addPermission($this->getReference('admin_access'));
        $lector->addPermission($this->getReference('program_access'));
        $lector->addPermission($this->getReference('program_manage_own_programs'));

        $organizer = $roles[Role::ORGANIZER];
        $organizer->addPermission($this->getReference('admin_access'));
        $organizer->addPermission($this->getReference('acl_manage'));
        $organizer->addPermission($this->getReference('cms_manage'));
        $organizer->addPermission($this->getReference('configuration_manage'));
        $organizer->addPermission($this->getReference('program_access'));
        $organizer->addPermission($this->getReference('program_manage_all_programs'));
        $organizer->addPermission($this->getReference('program_manage_schedule'));
        $organizer->addPermission($this->getReference('program_manage_rooms'));
        $organizer->addPermission($this->getReference('program_manage_categories'));
        $organizer->addPermission($this->getReference('program_choose_programs'));
        $organizer->addPermission($this->getReference('users_manage'));
        $organizer->addPermission($this->getReference('mailing_manage'));

        $admin = $roles[Role::ADMIN];
        $admin->setRegisterable(false);
        $admin->addPermission($this->getReference('admin_access'));
        $admin->addPermission($this->getReference('acl_manage'));
        $admin->addPermission($this->getReference('cms_manage'));
        $admin->addPermission($this->getReference('configuration_manage'));
        $admin->addPermission($this->getReference('program_access'));
        $admin->addPermission($this->getReference('program_manage_all_programs'));
        $admin->addPermission($this->getReference('program_manage_schedule'));
        $admin->addPermission($this->getReference('program_manage_rooms'));
        $admin->addPermission($this->getReference('program_manage_categories'));
        $admin->addPermission($this->getReference('program_choose_programs'));
        $admin->addPermission($this->getReference('users_manage'));
        $admin->addPermission($this->getReference('mailing_manage'));

        foreach ($roles as $key => $value) {
            $manager->persist($value);
        }
        $manager->flush();

        foreach ($roles as $key => $value) {
            $this->addReference($key, $value);
        }
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('App\Commands\Fixtures\PermissionFixture');
    }
}