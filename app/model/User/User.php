<?php

namespace App\Model\User;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\Settings\CustomInput\CustomInput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita uživatele.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\Table(name="user")
 */
class User
{
    use Identifier;

    /**
     * Uživatelské jméno skautIS.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $username;

    /**
     * E-mail.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * Role.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="users", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * Přihlášené programy.
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Program", inversedBy="attendees", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     * @var ArrayCollection
     */
    protected $programs;

    /**
     * Schválený.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $approved = TRUE;

    /**
     * Jméno.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $firstName;

    /**
     * Příjmení.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $lastName;

    /**
     * Přezdívka.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $nickName;

    /**
     * Zobrazované jméno - Příjmení Jméno (Přezdívka).
     * @ORM\Column(type="string")
     * @var string
     */
    protected $displayName;

    /**
     * Bezpečnostní kód.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $securityCode;

    /**
     * Propojený účet.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $member = FALSE;

    /**
     * Jednotka.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $unit;

    /**
     * Pohlaví.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $sex;

    /**
     * Datum narození.
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    protected $birthdate;

    /**
     * Id uživatele ve skautIS.
     * @ORM\Column(type="integer", unique=true, name="skautis_user_id")
     * @var int
     */
    protected $skautISUserId;

    /**
     * Id osoby ve skautIS.
     * @ORM\Column(type="integer", unique=true, name="skautis_person_id")
     * @var int
     */
    protected $skautISPersonId;

    /**
     * Datum prvního přihlášení.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $firstLogin;

    /**
     * Datum posledního přihlášení.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $lastLogin;

    /**
     * O mně.
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $about;

    /**
     * Ulice.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $street;

    /**
     * Město.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $city;

    /**
     * Poštovní směrovací číslo.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $postcode;

    /**
     * Stát.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $state;

    /**
     * Platební metoda.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $paymentMethod;

    /**
     * Datum zaplacení.
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $paymentDate;

    /**
     * Variabilní symbol.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $variableSymbol;

    /**
     * Zúčastnil se.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $attended = FALSE;

    /**
     * Příjezd.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $arrival;

    /**
     * Odjezd.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $departure;

    /**
     * Typ členství. NEPOUŽÍVÁ SE.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $membershipType;

    /**
     * Kategorie členství. NEPOUŽÍVÁ SE.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $membershipCategory;

    /**
     * Datum vytištění dokladu o zaplacení.
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    protected $incomeProofPrintedDate;

    /**
     * Hodnoty vlastních polí přihlášky.
     * @ORM\OneToMany(targetEntity="\App\Model\User\CustomInputValue\CustomInputValue", mappedBy="user", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $customInputValues;

    /**
     * Neveřejná poznámka.
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

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        if (!$this->isInRole($role))
            $this->roles->add($role);
    }

    /**
     * @param Role $role
     * @return bool
     */
    public function removeRole(Role $role)
    {
        return $this->roles->removeElement($role);
    }

    /**
     * Je uživatel v roli?
     * @param Role $role
     * @return bool
     */
    public function isInRole(Role $role)
    {
        return $this->roles->filter(function ($item) use ($role) {
                return $item == $role;
        })->count() != 0;
    }

    /**
     * Má uživatel oprávnění k prostředku?
     * @param $resource
     * @param $permission
     * @return bool
     */
    public function isAllowed($resource, $permission)
    {
        foreach ($this->roles as $r) {
            foreach ($r->getPermissions() as $p) {
                if ($p->getResource()->getName() == $resource && $p->getName() == $permission)
                    return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Je uživatel oprávněn upravovat blok?
     * @param Block $block
     * @return bool
     */
    public function isAllowedModifyBlock(Block $block)
    {
        if ($this->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS))
            return TRUE;

        if ($this->isAllowed(Resource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS) && $block->getLector() == $this)
            return TRUE;

        return FALSE;
    }

    /**
     * Vrací platící role uživatele.
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getPayingRoles()
    {
        return $this->roles->filter(function ($item) {
            return $item->getFee() > 0;
        });
    }

    /**
     * Je uživatel platící (nemá žádnou neplatící roli)?
     * @return bool
     */
    public function isPaying()
    {
        return $this->roles->filter(function ($item) {
                return $item->getFee() == 0;
        })->count() == 0;
    }

    /**
     * Má uživatel zaplaceno?
     * @return bool
     */
    public function hasPaid()
    {
        return $this->paymentDate !== NULL;
    }

    /**
     * Vrací poplatek uživatele. Pokud je platící - součet poplatků rolí.
     * @return int
     */
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

    /**
     * Vrací poplatek slovy.
     * @return mixed|string
     */
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
     * @param Program $program
     */
    public function addProgram(Program $program)
    {
        if (!$this->programs->contains($program)) {
            $this->programs->add($program);
            $program->addAttendee($this);
        }
    }

    /**
     * @param Program $program
     * @return bool
     */
    public function removeProgram(Program $program)
    {
        return $this->programs->removeElement($program);
    }

    /**
     * Má uživatel přihlášený program z bloku?
     * @param Block $block
     * @return bool
     */
    public function hasProgramBlock(Block $block)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('block_id', $block->getId())
        );

        return !$this->programs->matching($criteria)->isEmpty();
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

    /**
     * Aktualizuje zobrazované jméno.
     */
    private function updateDisplayName()
    {
        $this->displayName = $this->lastName . " " . $this->firstName;
        if ($this->nickName != NULL)
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
     * Má propojený účet?
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
    public function getAge()
    {
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
     * Vrátí variabilní symbol bez #.
     * @return string
     */
    public function getVariableSymbol()
    {
        return substr($this->variableSymbol, 0, 8);
    }

    /**
     * Nastaví variabilní symbol, pokud je změněn oproti vygenerovanému, přidá se na konec #.
     * @param string $variableSymbol
     */
    public function setVariableSymbol($variableSymbol)
    {
        if (!$this->variableSymbol)
            $this->variableSymbol = $variableSymbol;
        elseif (substr($this->variableSymbol, 0, 8) != $variableSymbol)
            $this->variableSymbol = $variableSymbol . '#';
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

    /**
     * @param CustomInput $customInput
     * @return mixed
     */
    public function getCustomInputValue(CustomInput $customInput)
    {
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
     * @return bool
     */
    public function hasDisplayArrivalDepartureRole()
    {
        $criteria = Criteria::create();

        if ($this->roles instanceof PersistentCollection && $this->roles->isInitialized())
            $criteria->where(Criteria::expr()->eq('displayArrivalDeparture', TRUE));
        else
            $criteria->where(Criteria::expr()->eq('display_arrival_departure', TRUE));  //problem s lazyloadingem u camelcase nazvu

        return !$this->roles->matching($criteria)->isEmpty();
    }

    /**
     * Vrací kategorie, ze kterých si uživatel může přihlašovat programy.
     * @param null $roles
     * @return array
     */
    public function getRegisterableCategories($roles = NULL)
    {
        $categories = [];
        if ($roles === NULL)
            $roles = $this->roles;
        foreach ($roles as $role) {
            foreach ($role->getRegisterableCategories() as $category) {
                $categories[] = $category;
            }
        }
        return $categories;
    }
}
