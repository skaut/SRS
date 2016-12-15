<?php

namespace App\Model\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\Table(name="user")
 */
class User
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string", unique=true) */
    protected $username;

    /** @ORM\Column(type="string") */
    protected $email;

    /** @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="users", cascade={"persist", "remove"}) */
    protected $roles;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Program", inversedBy="attendees", cascade={"persist", "remove"})
     * @ORM\OrderBy({"start" = "ASC"})
     */
    protected $programs;

    /** @ORM\Column(type="boolean") */
    protected $approved = True;

    /** @ORM\Column(type="string") */
    protected $firstName;

    /** @ORM\Column(type="string") */
    protected $lastName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $nickName;

    /** @ORM\Column(type="string") */
    protected $displayName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $securityCode;

    /** @ORM\Column(type="boolean") */
    protected $member = false;

    /** @ORM\Column(type="string", nullable=true) */
    protected $unit;

    /** @ORM\Column(type="string", nullable=true) */
    protected $sex;

    /** @ORM\Column(type="date") */
    protected $birthdate;

    /** @ORM\Column(type="integer", unique=true) */
    protected $skautISUserId;

    /** @ORM\Column(type="integer", unique=true) */
    protected $skautISPersonId;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $firstLogin;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $lastLogin;

    /** @ORM\Column(type="text", nullable=true) */
    protected $about;

    /** @ORM\Column(type="string", nullable=true) */
    protected $street;

    /** @ORM\Column(type="string", nullable=true) */
    protected $city;

    /** @ORM\Column(type="string", nullable=true) */
    protected $postcode;

    /** @ORM\Column(type="string", nullable=true) */
    protected $state;

    /** @ORM\Column(type="string", nullable=true) */
    protected $paymentMethod;

    /** @ORM\Column(type="date", nullable=true) */
    protected $paymentDate;

    /** @ORM\Column(type="string", nullable=true) */
    protected $variableSymbol;

    /** @ORM\Column(type="boolean") */
    protected $attended = false;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $arrival;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $departure;

    /** @ORM\Column(type="string", nullable=true) */
    protected $membershipType;

    /** @ORM\Column(type="string", nullable=true) */
    protected $membershipCategory;

    /** @ORM\Column(type="date", nullable=true) */
    protected $incomeProofPrintedDate;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean0 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean1 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean2 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean3 = false;

    /** @ORM\Column(type="boolean") */
    protected $customBoolean4 = false;

    /** @ORM\Column(type="text", nullable=true) */
    protected $customText0;

    /** @ORM\Column(type="text", nullable=true) */
    protected $customText1;

    /** @ORM\Column(type="text", nullable=true) */
    protected $note;

    public function __construct($username)
    {
        $this->username = $username;
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->programs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getPrograms()
    {
        return $this->programs;
    }

    /**
     * @param mixed $programs
     */
    public function setPrograms($programs)
    {
        $this->programs = $programs;
    }

    /**
     * @return mixed
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param mixed $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param mixed $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * @return mixed
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @param mixed $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * @return mixed
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param mixed $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @return mixed
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param mixed $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return mixed
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param mixed $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return mixed
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param mixed $birthdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return mixed
     */
    public function getSkautISUserId()
    {
        return $this->skautISUserId;
    }

    /**
     * @param mixed $skautISUserId
     */
    public function setSkautISUserId($skautISUserId)
    {
        $this->skautISUserId = $skautISUserId;
    }

    /**
     * @return mixed
     */
    public function getSkautISPersonId()
    {
        return $this->skautISPersonId;
    }

    /**
     * @param mixed $skautISPersonId
     */
    public function setSkautISPersonId($skautISPersonId)
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    /**
     * @return mixed
     */
    public function getFirstLogin()
    {
        return $this->firstLogin;
    }

    /**
     * @param mixed $firstLogin
     */
    public function setFirstLogin($firstLogin)
    {
        $this->firstLogin = $firstLogin;
    }

    /**
     * @return mixed
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param mixed $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return mixed
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * @param mixed $about
     */
    public function setAbout($about)
    {
        $this->about = $about;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param mixed $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param mixed $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return mixed
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * @param mixed $paymentDate
     */
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;
    }

    /**
     * @return mixed
     */
    public function getVariableSymbol()
    {
        return $this->variableSymbol;
    }

    /**
     * @param mixed $variableSymbol
     */
    public function setVariableSymbol($variableSymbol)
    {
        $this->variableSymbol = $variableSymbol;
    }

    /**
     * @return mixed
     */
    public function getAttended()
    {
        return $this->attended;
    }

    /**
     * @param mixed $attended
     */
    public function setAttended($attended)
    {
        $this->attended = $attended;
    }

    /**
     * @return mixed
     */
    public function getArrival()
    {
        return $this->arrival;
    }

    /**
     * @param mixed $arrival
     */
    public function setArrival($arrival)
    {
        $this->arrival = $arrival;
    }

    /**
     * @return mixed
     */
    public function getDeparture()
    {
        return $this->departure;
    }

    /**
     * @param mixed $departure
     */
    public function setDeparture($departure)
    {
        $this->departure = $departure;
    }

    /**
     * @return mixed
     */
    public function getMembershipType()
    {
        return $this->membershipType;
    }

    /**
     * @param mixed $membershipType
     */
    public function setMembershipType($membershipType)
    {
        $this->membershipType = $membershipType;
    }

    /**
     * @return mixed
     */
    public function getMembershipCategory()
    {
        return $this->membershipCategory;
    }

    /**
     * @param mixed $membershipCategory
     */
    public function setMembershipCategory($membershipCategory)
    {
        $this->membershipCategory = $membershipCategory;
    }

    /**
     * @return mixed
     */
    public function getIncomeProofPrintedDate()
    {
        return $this->incomeProofPrintedDate;
    }

    /**
     * @param mixed $incomeProofPrintedDate
     */
    public function setIncomeProofPrintedDate($incomeProofPrintedDate)
    {
        $this->incomeProofPrintedDate = $incomeProofPrintedDate;
    }

    /**
     * @return mixed
     */
    public function getCustomBoolean0()
    {
        return $this->customBoolean0;
    }

    /**
     * @param mixed $customBoolean0
     */
    public function setCustomBoolean0($customBoolean0)
    {
        $this->customBoolean0 = $customBoolean0;
    }

    /**
     * @return mixed
     */
    public function getCustomBoolean1()
    {
        return $this->customBoolean1;
    }

    /**
     * @param mixed $customBoolean1
     */
    public function setCustomBoolean1($customBoolean1)
    {
        $this->customBoolean1 = $customBoolean1;
    }

    /**
     * @return mixed
     */
    public function getCustomBoolean2()
    {
        return $this->customBoolean2;
    }

    /**
     * @param mixed $customBoolean2
     */
    public function setCustomBoolean2($customBoolean2)
    {
        $this->customBoolean2 = $customBoolean2;
    }

    /**
     * @return mixed
     */
    public function getCustomBoolean3()
    {
        return $this->customBoolean3;
    }

    /**
     * @param mixed $customBoolean3
     */
    public function setCustomBoolean3($customBoolean3)
    {
        $this->customBoolean3 = $customBoolean3;
    }

    /**
     * @return mixed
     */
    public function getCustomBoolean4()
    {
        return $this->customBoolean4;
    }

    /**
     * @param mixed $customBoolean4
     */
    public function setCustomBoolean4($customBoolean4)
    {
        $this->customBoolean4 = $customBoolean4;
    }

    /**
     * @return mixed
     */
    public function getCustomText0()
    {
        return $this->customText0;
    }

    /**
     * @param mixed $customText0
     */
    public function setCustomText0($customText0)
    {
        $this->customText0 = $customText0;
    }

    /**
     * @return mixed
     */
    public function getCustomText1()
    {
        return $this->customText1;
    }

    /**
     * @param mixed $customText1
     */
    public function setCustomText1($customText1)
    {
        $this->customText1 = $customText1;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param mixed $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }
}