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
     * @param stdClass $skautISUser
     * @param stdClass $skautISPerson
     * @param mixed $roles
     * @return \SRS\Model\User
     */
    public static function createFromSkautIS($skautISUser, $skautISPerson, $role) {
        $user = new \SRS\Model\User($skautISUser->UserName);
        $user->role = $role;
        $user->skautISUserId = $skautISUser->ID;
        $user->skautISPersonId = $skautISUser->ID_Person;
        $user->email = $skautISPerson->Email;
        $user->firstName = $skautISPerson->FirstName;
        $user->lastName = $skautISPerson->LastName;
        $user->nickName = $skautISPerson->NickName;
        $user->displayName = $skautISPerson->DisplayName;
        $user->sex = $skautISPerson->ID_Sex;
        $birthday = \explode("T", $skautISPerson->Birthday);
        $user->birthdate = new \DateTime($birthday[0]);

        $user->street = $skautISPerson->Street;
        $user->city = $skautISPerson->City;
        $user->postcode = $skautISPerson->Postcode;
        $user->state = $skautISPerson->State;

        return $user;

    }

    /**
     * @param \SRS\Model\User $SRSUser
     * @param  stdClass $skautISPerson
     * @return stdClass
     */
    public static function updateSkautISPerson($SRSUser, $skautISPerson) {

        $skautISPerson->FirstName = $SRSUser->firstName;
        $skautISPerson->LastName = $SRSUser->lastName;
        $skautISPerson->NickName = $SRSUser->NickName;
        $skautISPerson->ID_Sex = $SRSUser->sex;
        //$skautISPerson->Sex = $sexChoices[$SRSUser->sex];
        $skautISPerson->Email = $SRSUser->email;
        $skautISPerson->Street = $SRSUser->street;
        $skautISPerson->City = $SRSUser->city;
        $skautISPerson->Postcode = $SRSUser->postcode;
        $skautISPerson->State = $SRSUser->state;
        $birthdate = $SRSUser->birthdate;
        $birthdate = $birthdate->format('Y-m-d');
        $birthdate .='T00:00:00';
        $skautISPerson->birthday = $birthdate;
        return $skautISPerson;

    }

    /**
     * @param int $skautISUserId
     * @param int $skautISPersonId
     * @param \Doctrine\Common\Collections\ArrayCollection $roles
     * @return \SRS\Model\User
     */
    public static function createRandom($skautISUserId, $skautISPersonId, $roles) {

        $sex_choices = array('male', 'female');
        $approved_choices = array(true, false);

        $user = new \SRS\Model\User(Strings::random());
        $user->skautISUserId = $skautISUserId;

        $user->skautISPersonId = $skautISPersonId;

        $user->role = $roles[mt_rand(0, sizeof($roles)-1)];
        $user->email = Strings::random(5) . '@' . Strings::random(5) . '.' . Strings::random(2);
        $user->firstName = Strings::random(8);
        $user->lastName = Strings::random(5);
        $user->nickName = Strings::random(6);
        $user->sex = $sex_choices[mt_rand(0, sizeof($sex_choices)-1)];
        $user->birthdate = new \DateTime('now');
        $user->approved = $approved_choices[mt_rand(0, 1)];

        return $user;
    }

}
