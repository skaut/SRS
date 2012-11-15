<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:25
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Factory;
class RoleFactory
{
    public static function createRoles() {
        $guest = new \SRS\Model\Role('guest');
        $registered = new \SRS\Model\Role('registered',$guest);
        $serviceTeam = new \SRS\Model\Role('serviceTeam', $registered);
        return array($guest, $registered, $serviceTeam);
    }

}
