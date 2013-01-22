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

        $acl = new \SRS\Model\Acl\Resource('ACL');
        $cms = new \SRS\Model\Acl\Resource('CMS');

        $manage_users_acl = new \SRS\Model\Acl\Permission('Spravovat uživatele', $acl);
        $manage_cms = new \SRS\Model\Acl\Permission('CMS', $cms);
        $manage_roles_acl = new \SRS\Model\Acl\Permission('Spravovat role', $acl);

        $roles[] = $guest = new \SRS\Model\Acl\Role('guest');
        $guest->registerable = False;
        $roles[] = $registered = new \SRS\Model\Acl\Role('Registrovaný');
        $registered->registerable = False;
        $roles[] = $atendee = new \SRS\Model\Acl\Role('Účastník');
        $roles[] = $serviceTeam = new \SRS\Model\Acl\Role('Servis Tým');
        $roles[] = $lector = new \SRS\Model\Acl\Role('Lektor');
        $roles[] = $organizer = new \SRS\Model\Acl\Role('Organizátor');
        $roles[] = $admin = new \SRS\Model\Acl\Role('Administrátor');
        $admin->registerable = False;
        $admin->permissions->add($manage_roles_acl);
        $admin->permissions->add($manage_cms);
        $admin->permissions->add($manage_users_acl);
        $organizer->permissions->add($manage_users_acl);
        //$organizer->permissions->add($allow_cms);

        return $roles;
    }







}
