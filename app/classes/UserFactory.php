<?php
/**
 * Date: 22.10.12
 * Time: 20:20
 * Author: Michal Májský
 */
namespace SRS\Factory;
use Nette\Utils\Strings;

/**
 * Obsluhuje tvorbu lokalnich uzivatelu ze skautIS uzivatelu a obracene
 */
class UserFactory
{
    /**
     * @param stdClass $skautISUser
     * @param stdClass $skautISPerson
     * @param \SRS\Model\Acl\Role $role
     * @return \SRS\Model\User
     */
    public static function createFromSkautIS($skautISUser, $skautISPerson)
    {
        $user = new \SRS\Model\User($skautISUser->UserName);
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
    public static function updateSkautISPerson($SRSUser, $skautISPerson)
    {

        $skautISPerson->FirstName = $SRSUser->firstName;
        $skautISPerson->LastName = $SRSUser->lastName;
        $skautISPerson->NickName = $SRSUser->NickName;
        $skautISPerson->ID_Sex = $SRSUser->sex;
        //$skautISPerson->Sex = $sexChoices[$SRSUser->sex];
        //$skautISPerson->Email = $SRSUser->email;
        $skautISPerson->Street = $SRSUser->street;
        $skautISPerson->City = $SRSUser->city;
        $skautISPerson->Postcode = $SRSUser->postcode;
        $skautISPerson->State = $SRSUser->state;
        $birthdate = $SRSUser->birthdate;
        $birthdate = $birthdate->format('Y-m-d');
        $birthdate .= 'T00:00:00';
        $skautISPerson->Birthday = $birthdate;
        return $skautISPerson;

    }

    /**
     * @param int $skautISUserId
     * @param int $skautISPersonId
     * @param \Doctrine\Common\Collections\ArrayCollection $roles
     * @return \SRS\Model\User
     */
    public static function createRandom($skautISUserId, $skautISPersonId, $roles)
    {

        $sex_choices = array('male', 'female');
        $approved_choices = array(true, false);

        $user = new \SRS\Model\User(Strings::random());
        $user->skautISUserId = $skautISUserId;

        $user->skautISPersonId = $skautISPersonId;

        $user->roles->add($roles[mt_rand(0, sizeof($roles) - 1)]);
        $user->email = Strings::random(5) . '@' . Strings::random(5) . '.' . Strings::random(2);
        $user->firstName = Strings::random(8);
        $user->lastName = Strings::random(5);
        $user->nickName = Strings::random(6);
        $user->displayName = $user->lastName . ' ' . $user->firstName . ' (' . $user->nickName . ')';
        $user->sex = $sex_choices[mt_rand(0, sizeof($sex_choices) - 1)];
        $user->birthdate = new \DateTime('now');
        $user->approved = $approved_choices[mt_rand(0, 1)];
        $user->attended = $approved_choices[mt_rand(0, 1)];
        $user->incomeProofPrintedDate = new \DateTime('now');

        $user->street = Strings::random(5);
        $user->city = Strings::random(5);
        $user->postcode = Strings::random(5);
        $user->state = Strings::random(5);

        return $user;
    }

}
