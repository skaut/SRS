<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 22.10.12
 * Time: 20:20
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Parsers;

class UserParser
{
    public static function createFromSkautIS($skautISUser, $skautISPerson) {
        $user = new \SRS\Model\User($skautISUser->UserName);
        $user->email = $skautISPerson->Email;
        $user->firstName = $skautISPerson->FirstName;
        $user->lastName = $skautISPerson->LastName;
        $user->nickName = $skautISPerson->NickName;
        $user->sex = $skautISPerson->ID_Sex;

        $birthday = \explode("T", $skautISPerson->Birthday);
        $user->birthdate = new \DateTime($birthday[0]);

        return $user;

    }

}
