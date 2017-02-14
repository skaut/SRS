<?php

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Program\Block;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\User\CustomInputValue\CustomInputValue;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\Table(name="user")
 */
class User
{
    use Identifier;

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
     * @ORM\Column(type="string")
     * @var string
     */
    protected $displayName;

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
     * @ORM\OneToMany(targetEntity="\App\Model\User\CustomInputValue\CustomInputValue", mappedBy="user", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $customInputValues;

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
        $this->roles = new ArrayCollection();
        $this->programs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    public function addRole(Role $role)
    {
        if (!$this->isInRole($role))
            $this->roles->add($role);
    }

    public function removeRole(Role $role)
    {
        return $this->roles->removeElement($role);
    }

    public function removeRoleAndNotAllowedPrograms($role, $nonregisteredRole) {
        $newRoles = new ArrayCollection($this->roles->toArray());
        $newRoles->removeElement($role);
        if ($newRoles->isEmpty())
            $newRoles->add($nonregisteredRole);
        $this->setRolesAndRemoveNotAllowedPrograms($newRoles);
    }

    public function setRolesAndRemoveNotAllowedPrograms($roles) {
        $registerableCategoriesOld = array();
        $registerableCategoriesNew = array();

        foreach ($this->getRegisterableCategories() as $category) {
            $registerableCategoriesOld[] = $category;
        }

        foreach ($this->getRegisterableCategories($roles) as $category) {
            $registerableCategoriesNew[] = $category;
        }

        $this->roles->clear();

        if (count($roles) == 1 && $roles[0]->getSystemName() == Role::NONREGISTERED) {
            $this->addRole($roles[0]);
            $this->programs->clear();
        }
        else {
            foreach ($roles as $role) {
                $this->roles->add($role);
            }

            foreach ($registerableCategoriesOld as $oldCategory) {
                if (!in_array($oldCategory, $registerableCategoriesNew))
                    $this->removeProgramsInCategory($oldCategory);
            }
        }
    }

    public function isInRole(Role $role)
    {
        return $this->roles->filter(function ($item) use ($role) {
            return $item == $role;
        })->count() != 0;
    }

    public function isAllowed($resource, $permission) {
        foreach ($this->roles as $r) {
            foreach ($r->getPermissions() as $p) {
                if ($p->getResource()->getName() == $resource && $p->getName() == $permission)
                    return true;
            }
        }
        return false;
    }

    public function isAllowedModifyBlock(Block $block) {
        if ($this->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS))
            return true;

        if ($this->isAllowed(Resource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS) && $block->getLector() == $this)
            return true;

        return false;
    }

    public function getPayingRoles()
    {
        return $this->roles->filter(function ($item) {
            return $item->getFee() > 0;
        });
    }

    public function isPaying()
    {
        return $this->roles->filter(function ($item) {
            return $item->getFee() == 0;
        })->count() == 0;
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
        $this->updateDisplayName();
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
        $this->updateDisplayName();
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
        $this->updateDisplayName();
    }

    /**
     * @return string $displayName
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    private function updateDisplayName()
    {
        $this->displayName = $this->lastName . " " . $this->firstName;
        if ($this->nickName != null)
            $this->displayName .= " (" . $this->nickName . ")";
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
    public function getAge() {
        return (new \DateTime())->diff($this->birthdate)->y;
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
     * @return ArrayCollection
     */
    public function getCustomInputValues()
    {
        return $this->customInputValues;
    }

    /**
     * @param ArrayCollection $customInputValues
     */
    public function setCustomInputValues($customInputValues)
    {
        $this->customInputValues = $customInputValues;
    }

    public function getCustomInputValue(CustomInput $customInput) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()
                ->eq('input', $customInput)
            );
        return $this->customInputValues->matching($criteria)->first();
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

    /**
     * Je uživatel v roli, u které se eviduje příjezd a odjezd?
     *
     * @return bool
     */
    public function hasDisplayArrivalDepartureRole()
    {
        $criteria = Criteria::create();

        if($this->roles instanceof PersistentCollection && $this->roles->isInitialized())
            $criteria->where(Criteria::expr()->eq('displayArrivalDeparture', true));
        else
            $criteria->where(Criteria::expr()->eq('display_arrival_departure', true));  //problem s lazyloadingem u camelcase nazvu

        return !$this->roles->matching($criteria)->isEmpty();
    }

    public function getRegisterableCategories($roles = null) {
        $categories = [];
        if ($roles === null)
            $roles = $this->roles;
        foreach ($roles as $role) {
            foreach ($role->getRegisterableCategories() as $category) {
                $categories[] = $category;
            }
        }
        return $categories;
    }


    private function removeProgramsInCategory($category) {
        foreach ($this->programs as $program) {
            if ($program->getBlock()->getCategory() === $category) {
                $this->programs->removeElement($program);
            }
        }
    }
}