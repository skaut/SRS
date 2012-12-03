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
        $admin = new \SRS\Model\Acl\Resource('Admin');

        $manage_acl = new \SRS\Model\Acl\Permission('Spravovat', $acl);
        $allow_admin = new \SRS\Model\Acl\Permission('Přístup', $admin);

        $roles[] = $guest = new \SRS\Model\Acl\Role('Anonym');
        $roles[] = $registered = new \SRS\Model\Acl\Role('Registrovaný',$guest);
        $roles[] = $atendee = new \SRS\Model\Acl\Role('Účastník', $registered);
        $roles[] = $serviceTeam = new \SRS\Model\Acl\Role('Servis Tým', $registered);
        $roles[] = $lector = new \SRS\Model\Acl\Role('Lektor', $serviceTeam);
        $roles[] = $organizer = new \SRS\Model\Acl\Role('Organizátor', $lector);
        $roles[] = $admin = new \SRS\Model\Acl\Role('Administrátor', $organizer);
        $admin->permissions->add($manage_acl);
        $admin->permissions->add($allow_admin);
        $organizer->permissions->add($allow_admin);

        return $roles;
    }







}
