<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 22.10.12
 * Time: 20:20
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Factory;
use Nette\Utils\Strings;

class UserFactory
{
    /**
     * @param int $skautISUser
     * @param int $skautISPerson
     * @return \SRS\Model\User
     */
    public static function createFromSkautIS($skautISUser, $skautISPerson) {
        $user = new \SRS\Model\User($skautISUser->UserName);
        $user->roles = array('registered');
        $user->skautISUserId = $skautISUser->ID;
        $user->skautISPersonId = $skautISUser->ID_Person;
        $user->email = $skautISPerson->Email;
        $user->firstName = $skautISPerson->FirstName;
        $user->lastName = $skautISPerson->LastName;
        $user->nickName = $skautISPerson->NickName;
        $user->sex = $skautISPerson->ID_Sex;
        $birthday = \explode("T", $skautISPerson->Birthday);
        $user->birthdate = new \DateTime($birthday[0]);

        return $user;

    }

    /**
     * @param int $skautISUserId
     * @param int $skautISPersonId
     * @return \SRS\Model\User
     */
    public static function createRandom($skautISUserId, $skautISPersonId) {
        $sex_choices = array('male', 'female');
        $role_choices = array('guest', 'registered', 'organizer');

        $user = new \SRS\Model\User(Strings::random());
        $user->skautISUserId = $skautISUserId;
        $user->skautISPersonId = $skautISPersonId;
        $user->roles = array($role_choices[mt_rand(0, sizeof($sex_choices)-1)]);
        $user->email = Strings::random(5) . '@' . Strings::random(5) . '.' . Strings::random(2);
        $user->firstName = Strings::random(8);
        $user->lastName = Strings::random(5);
        $user->nickName = Strings::random(6);
        $user->sex = $sex_choices[mt_rand(0, sizeof($sex_choices)-1)];
        $user->birthdate = new \DateTime('now');
        return $user;
    }

}
