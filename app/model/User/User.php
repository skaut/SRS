<?php

namespace App\Model\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\Table(name="user")
 */
class User
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $username;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="users", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Program", inversedBy="attendees", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     * @var ArrayCollection
     */
    protected $programs;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $approved = true;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $nickName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $securityCode;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $member = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $unit;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $sex;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    protected $birthdate;

    /**
     * @ORM\Column(type="integer", unique=true, name="skautis_user_id")
     * @var int
     */
    protected $skautISUserId;

    /**
     * @ORM\Column(type="integer", unique=true, name="skautis_person_id")
     * @var int
     */
    protected $skautISPersonId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $firstLogin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $lastLogin;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $about;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $street;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $postcode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $state;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $paymentMethod;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $paymentDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $variableSymbol;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $attended = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $arrival;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $departure;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $membershipType;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $membershipCategory;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $incomeProofPrintedDate;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $customBoolean0 = false;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $customBoolean1 = false;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $customBoolean2 = false;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $customBoolean3 = false;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $customBoolean4 = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $customText0;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $customText1;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $note;

    /**
     * User constructor.
     * @param string $username
     */
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
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
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
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param ArrayCollection $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function addRole($role)
    {
        if (!$this->isInRole($role->getName()))
            $this->roles->add($role);
    }

    public function removeRole($role)
    {
        return $this->roles->removeElement($role);
    }

    public function isInRole($roleName)
    {
        foreach ($this->roles as $role) {
            if ($role->getName() == $roleName)
                return true;
        }
        return false;
    }

    public function getPayingRoles()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('fee', 0));
        return $this->roles->matching($criteria);
    }

    public function isPaying()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('fee', 0));
        return !$this->roles->matching($criteria)->isEmpty();
    }

    public function hasPaid()
    {
        return $this->paymentDate !== null;
    }

    public function getFee()
    {
        if (!$this->isPaying())
            return 0;

        $fee = 0;

        foreach ($this->getPayingRoles() as $role) {
            $fee += $role->getFee();
        }

        return $fee;
    }

    public function getFeeWords()
    {
        $numbersWords = new \Numbers_Words();
        $feeWord = $numbersWords->toWords($this->getFee(), 'cs');
        $feeWord = iconv('windows-1250', 'UTF-8', $feeWord);
        $feeWord = str_replace(" ", "", $feeWord);
        return $feeWord;
    }

    /**
     * @return ArrayCollection
     */
    public function getPrograms()
    {
        return $this->programs;
    }

    /**
     * @param ArrayCollection $programs
     */
    public function setPrograms($programs)
    {
        $this->programs = $programs;
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->approved;
    }

    /**
     * @param bool $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
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
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param string $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    public function getDisplayName()
    {
        $displayName = $this->lastName . " " . $this->firstName;
        if ($this->nickName != null)
            $displayName .= " (" . $this->nickName . ")";
        return $displayName;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @param string $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * @return bool
     */
    public function isMember()
    {
        return $this->member;
    }

    /**
     * @param bool $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param \DateTime $birthdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return int
     */
    public function getSkautISUserId()
    {
        return $this->skautISUserId;
    }

    /**
     * @param int $skautISUserId
     */
    public function setSkautISUserId($skautISUserId)
    {
        $this->skautISUserId = $skautISUserId;
    }

    /**
     * @return int
     */
    public function getSkautISPersonId()
    {
        return $this->skautISPersonId;
    }

    /**
     * @param int $skautISPersonId
     */
    public function setSkautISPersonId($skautISPersonId)
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    /**
     * @return \DateTime
     */
    public function getFirstLogin()
    {
        return $this->firstLogin;
    }

    /**
     * @param \DateTime $firstLogin
     */
    public function setFirstLogin($firstLogin)
    {
        $this->firstLogin = $firstLogin;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string
     */
    public function getAbout()
    {
        return $this->about;
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
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return \DateTime
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * @param \DateTime $paymentDate
     */
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;
    }

    /**
     * @return string
     */
    public function getVariableSymbol()
    {
        return $this->variableSymbol;
    }

    /**
     * @param string $variableSymbol
     */
    public function setVariableSymbol($variableSymbol)
    {
        $this->variableSymbol = $variableSymbol;
    }

    public function getVariableSymbolWithCode($code)
    {
        return $code . $this->variableSymbol;
    }

    /**
     * @return bool
     */
    public function isAttended()
    {
        return $this->attended;
    }

    /**
     * @param bool $attended
     */
    public function setAttended($attended)
    {
        $this->attended = $attended;
    }

    /**
     * @return \DateTime
     */
    public function getArrival()
    {
        return $this->arrival;
    }

    /**
     * @param \DateTime $arrival
     */
    public function setArrival($arrival)
    {
        $this->arrival = $arrival;
    }

    /**
     * @return \DateTime
     */
    public function getDeparture()
    {
        return $this->departure;
    }

    /**
     * @param \DateTime $departure
     */
    public function setDeparture($departure)
    {
        $this->departure = $departure;
    }

    /**
     * @return string
     */
    public function getMembershipType()
    {
        return $this->membershipType;
    }

    /**
     * @param string $membershipType
     */
    public function setMembershipType($membershipType)
    {
        $this->membershipType = $membershipType;
    }

    /**
     * @return string
     */
    public function getMembershipCategory()
    {
        return $this->membershipCategory;
    }

    /**
     * @param string $membershipCategory
     */
    public function setMembershipCategory($membershipCategory)
    {
        $this->membershipCategory = $membershipCategory;
    }

    /**
     * @return \DateTime
     */
    public function getIncomeProofPrintedDate()
    {
        return $this->incomeProofPrintedDate;
    }

    /**
     * @param \DateTime $incomeProofPrintedDate
     */
    public function setIncomeProofPrintedDate($incomeProofPrintedDate)
    {
        $this->incomeProofPrintedDate = $incomeProofPrintedDate;
    }

    /**
     * @return bool
     */
    public function isCustomBoolean0()
    {
        return $this->customBoolean0;
    }

    /**
     * @param bool $customBoolean0
     */
    public function setCustomBoolean0($customBoolean0)
    {
        $this->customBoolean0 = $customBoolean0;
    }

    /**
     * @return bool
     */
    public function isCustomBoolean1()
    {
        return $this->customBoolean1;
    }

    /**
     * @param bool $customBoolean1
     */
    public function setCustomBoolean1($customBoolean1)
    {
        $this->customBoolean1 = $customBoolean1;
    }

    /**
     * @return bool
     */
    public function isCustomBoolean2()
    {
        return $this->customBoolean2;
    }

    /**
     * @param bool $customBoolean2
     */
    public function setCustomBoolean2($customBoolean2)
    {
        $this->customBoolean2 = $customBoolean2;
    }

    /**
     * @return bool
     */
    public function isCustomBoolean3()
    {
        return $this->customBoolean3;
    }

    /**
     * @param bool $customBoolean3
     */
    public function setCustomBoolean3($customBoolean3)
    {
        $this->customBoolean3 = $customBoolean3;
    }

    /**
     * @return bool
     */
    public function isCustomBoolean4()
    {
        return $this->customBoolean4;
    }

    /**
     * @param bool $customBoolean4
     */
    public function setCustomBoolean4($customBoolean4)
    {
        $this->customBoolean4 = $customBoolean4;
    }

    /**
     * @return string
     */
    public function getCustomText0()
    {
        return $this->customText0;
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
    public function getCustomText1()
    {
        return $this->customText1;
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
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    public function isDisplayArrivalDeparture()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        return !$this->roles->matching($criteria)->isEmpty();
    }

}