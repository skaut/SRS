<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:25
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Factory;
class AclFactory
{
    //protected $roles;

    public static function createRoles() {
        $roles = array();
        $roles[] = $guest = new \SRS\Model\Acl\Role('guest');
        $roles[] = $registered = new \SRS\Model\Acl\Role('Registrovaný');
        $roles[] = $attendee = new \SRS\Model\Acl\Role('Účastník');
        $roles[] = $serviceTeam = new \SRS\Model\Acl\Role('Servis Tým');
        $roles[] = $lector = new \SRS\Model\Acl\Role('Lektor');
        $roles[] = $organizer = new \SRS\Model\Acl\Role('Organizátor');
        $roles[] = $admin = new \SRS\Model\Acl\Role('Administrátor');

        $admin->registerable = False;
        $registered->registerable = False;
        $guest->registerable = False;
        $attendee->approvedAfterRegistration = true;
        $attendee->pays = true;

        $backend = new \SRS\Model\Acl\Resource('Administrace');
        $acl = new \SRS\Model\Acl\Resource('ACL');
        $cms = new \SRS\Model\Acl\Resource('CMS');
        $program = new \SRS\Model\Acl\Resource('Program');
        $configuration = new \SRS\Model\Acl\Resource('Konfigurace');
        $evidence = new \SRS\Model\Acl\Resource('Evidence');

        $admin_access = new \SRS\Model\Acl\Permission('Přístup', $backend);
        $admin->permissions->add($admin_access);
        $organizer->permissions->add($admin_access);
        $lector->permissions->add($admin_access);
        $serviceTeam->permissions->add($admin_access);


        $acl_edit = new \SRS\Model\Acl\Permission('Spravovat', $acl);
        $admin->permissions->add($acl_edit);
        $organizer->permissions->add($acl_edit);

        $cms_edit = new \SRS\Model\Acl\Permission('Spravovat', $cms);
        $admin->permissions->add($cms_edit);
        $organizer->permissions->add($cms_edit);

        $configuration_edit = new \SRS\Model\Acl\Permission('Spravovat', $configuration);
        $admin->permissions->add($configuration_edit);
        $organizer->permissions->add($configuration_edit);


        $program_allow = new \SRS\Model\Acl\Permission('Přístup', $program);
        $admin->permissions->add($program_allow);
        $organizer->permissions->add($program_allow);
        $lector->permissions->add($program_allow);
        $serviceTeam->permissions->add($program_allow);

        $program_edit_mine = new \SRS\Model\Acl\Permission('Spravovat vlastní Programy', $program);
        $lector->permissions->add($program_edit_mine);

        $program_edit = new \SRS\Model\Acl\Permission('Spravovat Všechny Programy', $program);
        $admin->permissions->add($program_edit);
        $organizer->permissions->add($program_edit);

        $program_harmonogram_edit = new \SRS\Model\Acl\Permission('Upravovat harmonogram', $program);
        $admin->permissions->add($program_harmonogram_edit);
        $organizer->permissions->add($program_harmonogram_edit);

        $program_choose = new \SRS\Model\Acl\Permission('Vybírat si programy', $program);
        $attendee->permissions->add($program_choose);

        $evidence_edit = new \SRS\Model\Acl\Permission('Spravovat', $evidence);
        $admin->permissions->add($evidence_edit);
        $organizer->permissions->add($evidence_edit);


        return $roles;
    }







}
