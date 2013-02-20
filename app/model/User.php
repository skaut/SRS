<?php

namespace SRS\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\Entity(repositoryClass="\SRS\Model\UserRepository")
 *
 * @property-read int $id
 * @property string $username
 * @property string $email
 * @property \SRS\Model\Acl\Role $role
 * @property \Doctrine\Common\Collections\ArrayCollection $programs
 * @property \Doctrine\Common\Collections\ArrayCollection $extensions

 * @property string $firstName
 * @property string $lastName
 * @property string $nickName
 * @property string $sex
 * @property \DateTime $birthdate
 * @property int $skautISUserId
 * @property int $skautISPersonId
 * @property bool $approved
 * @property bool $paid
 * @property bool $incomeProofPrinted
 * @property bool $attended
 * @property string $displayName
 * @property string $state
 * @property string $city
 * @property string $street
 * @property string $postcode
 * @property string $unit
 * @property string $about
 */
class User extends BaseEntity
{

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $username;
    /**
     * @ORM\Column
     * @var string
     */
    protected $email;

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Role", inversedBy="users")
     * @var \SRS\Model\Acl\Role
     */
    protected $role;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\Model\Program\Program", mappedBy="attendees", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     */
    protected $programs;


    /**
     * @ORM\Column(type="boolean")
     */
    protected $approved = True;


//    protected $roles;

     /**
     * @ORM\Column
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $nickName;


    /**
     * @var string
     * @ORM\Column
     */
    protected $displayName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $sex;

    /**
     * @ORM\Column(type="date")
     * @var string
     */
    protected $birthdate;


   /**
    * @var int
    * @ORM\Column(type="integer", unique=true)
   */
    protected $skautISUserId;



    /**
     * @var int
     * @ORM\Column(type="integer", unique=true)
     */
    protected $skautISPersonId;


    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $about;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $street;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $postcode;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $unit;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $paid = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $attended = false;


    /**
     * @var bool
     * @ORM\Column(type="boolean")
    */
    protected $incomeProofPrinted = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $customBoolean0 = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $customBoolean1 = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $customBoolean2 = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $customBoolean3 = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $customBoolean4 = false;


    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $customText0;


    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $customText1;

    public function getCustomBoolean($index)
    {
        $value = $this->{"customBoolean$index"};
        return $value;
    }

    public function getCustomText($index)
    {
        $value = $this->{"customText$index"};
        return $value;
    }


    public function getAttended()
    {
        return $this->attended;
    }

    public function setAttended($attended)
    {
        $this->attended = $attended;
    }



    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    public function getPaid()
    {
        return $this->paid;
    }



    /**
     * @param string $about
     */
    public function setAbout($about)
    {
        $this->about = $about;
    }

    /**
     * @return string
     */
    public function getAbout()
    {
        return $this->about;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function getCity()
    {
        return $this->city;
    }

    /**
     * @ORM\Column(nullable=true)
     */
    protected $phone;


    /**
     * @param string
     * @return User
     */
    public function __construct($username)
    {
        $this->username = static::normalizeString($username);
        //$this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * @param string $birhdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return string
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getRole() {
        return $this->role;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function getPrograms() {
        return $this->programs;
    }

    public function setPrograms($programs) {
        $this->programs = $programs;
    }

    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    /**
     * @return string
     */
    public function getNickName()
    {
        return $this->nickName;
    }


    /**
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }


    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = static::normalizeString($email);
        return $this;
    }

    /**
     * @param $skautISPersonId
     */
    public function setSkautISPersonId($skautISPersonId)
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    /**
     * @return int
     */
    public function getSkautISPersonId()
    {
        return $this->skautISPersonId;
    }

    /**
     * @param $skautISUserId
     */
    public function setSkautISUserId($skautISUserId)
    {
        $this->skautISUserId = $skautISUserId;
    }

    /**
     * @return int
     */
    public function getSkautISUserId()
    {
        return $this->skautISUserId;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }



    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    public function getPostcode()
    {
        return $this->postcode;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param boolean $customBoolean0
     */
    public function setCustomBoolean0($customBoolean0)
    {
        $this->customBoolean0 = $customBoolean0;
    }

    /**
     * @return boolean
     */
    public function getCustomBoolean0()
    {
        return $this->customBoolean0;
    }

    /**
     * @param boolean $customBoolean1
     */
    public function setCustomBoolean1($customBoolean1)
    {
        $this->customBoolean1 = $customBoolean1;
    }

    /**
     * @return boolean
     */
    public function getCustomBoolean1()
    {
        return $this->customBoolean1;
    }

    /**
     * @param boolean $customBoolean2
     */
    public function setCustomBoolean2($customBoolean2)
    {
        $this->customBoolean2 = $customBoolean2;
    }

    /**
     * @return boolean
     */
    public function getCustomBoolean2()
    {
        return $this->customBoolean2;
    }

    /**
     * @param boolean $customBoolean3
     */
    public function setCustomBoolean3($customBoolean3)
    {
        $this->customBoolean3 = $customBoolean3;
    }

    /**
     * @return boolean
     */
    public function getCustomBoolean3()
    {
        return $this->customBoolean3;
    }

    /**
     * @param boolean $customBoolean4
     */
    public function setCustomBoolean4($customBoolean4)
    {
        $this->customBoolean4 = $customBoolean4;
    }

    /**
     * @return boolean
     */
    public function getCustomBoolean4()
    {
        return $this->customBoolean4;
    }

    /**
     * @param string $customText0
     */
    public function setCustomText0($customText0)
    {
        $this->customText0 = $customText0;
    }

    /**
     * @return string
     */
    public function getCustomText0()
    {
        return $this->customText0;
    }

    /**
     * @param string $customText1
     */
    public function setCustomText1($customText1)
    {
        $this->customText1 = $customText1;
    }

    /**
     * @return string
     */
    public function getCustomText1()
    {
        return $this->customText1;
    }


    /**
     * @param boolean $incomeProofPrinted
     */
    public function setIncomeProofPrinted($incomeProofPrinted)
    {
        $this->incomeProofPrinted = $incomeProofPrinted;
    }

    /**
     * @return boolean
     */
    public function getIncomeProofPrinted()
    {
        return $this->incomeProofPrinted;
    }

    /**
     * @param string
     * @return string
     */
    protected static function normalizeString($s)
    {
        $s = trim($s);
        return $s === "" ? NULL : $s;
    }

    public function hasOtherProgram($program, $basicBlockDuration) {
        foreach ($this->programs as $otherProgram) {
            if ($otherProgram->id == $program->id) continue;
            if ($otherProgram->start == $program->start) return true;
            if ($otherProgram->start > $program->start && $otherProgram->start < $program->countEnd($basicBlockDuration)) return true;
            if ($otherProgram->countEnd($basicBlockDuration) > $program->start && $otherProgram->countEnd($basicBlockDuration) < $program->countEnd($basicBlockDuration) ) return true;
            if ($otherProgram->start < $program->start && $otherProgram->countEnd($basicBlockDuration) > $program->countEnd($basicBlockDuration) ) return true;
        }
        return false;
    }

    public function countAge()
    {
        $today = new \DateTime('now');
        $interval = $today->diff($this->birthdate);
        return $interval->y;
    }
}


class UserRepository extends \Nella\Doctrine\Repository
{
//    public function findInRole($roleName)
//    {
//        return $this->_em->findAllBy(array('role.name' => $roleName));
//    }


}