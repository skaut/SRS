<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="RoleRepository")
 * @ORM\Table(name="role")
 */
class Role
{
    const GUEST = 'guest';
    const UNREGISTERED = 'unregistered';
    const UNAPPROVED = 'unapproved';
    const ATTENDEE = 'attendee';
    const SERVICE_TEAM = 'service_team';
    const LECTOR = 'lector';
    const ORGANIZER = 'organizer';
    const ADMIN = 'admin';

    public static $roles = [
        self::GUEST,
        self::UNREGISTERED,
        self::UNAPPROVED,
        self::ATTENDEE,
        self::SERVICE_TEAM,
        self::LECTOR,
        self::ORGANIZER,
        self::ADMIN
    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="roles", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $users;

    /**
     * @ORM\ManyToMany(targetEntity="Permission", inversedBy="roles", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $permissions;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Page", mappedBy="roles", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $pages;

    /**
     * Pokud je role systemova, nelze ji smazat
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $registerable = true;

    /**
     * Je role po registraci rovnou schvalena?
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $approvedAfterRegistration = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $registerableFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $registerableTo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $fee;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var bool
     */
    protected $displayArrivalDeparture;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $syncedWithSkautIS = true;

    /**
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="role_role_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     * @var ArrayCollection
     */
    protected $incompatibleRoles;

    /**
     * @ORM\ManyToMany(targetEntity="Role", mappedBy="requiredRoles", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $requiredByRole;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="requiredByRole", cascade={"persist"})
     * @ORM\JoinTable(name="role_role_required",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
     *      )
     * @var ArrayCollection
     */
    protected $requiredRoles;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Category", mappedBy="registerableRoles", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $registerableCategories;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $redirectAfterLogin;

    /**
     * Role constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->incompatibleRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->requiredByRole = new \Doctrine\Common\Collections\ArrayCollection();
        $this->requiredRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->registerableCategories = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param ArrayCollection $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return ArrayCollection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param ArrayCollection $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    public function addPermission($permission)
    {
        $this->permissions->add($permission);
    }

    /**
     * @return ArrayCollection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param ArrayCollection $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return bool
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * @param bool $system
     */
    public function setSystem($system)
    {
        $this->system = $system;
    }

    /**
     * @return bool
     */
    public function isRegisterable()
    {
        return $this->registerable;
    }

    /**
     * @param bool $registerable
     */
    public function setRegisterable($registerable)
    {
        $this->registerable = $registerable;
    }

    /**
     * @return bool
     */
    public function isApprovedAfterRegistration()
    {
        return $this->approvedAfterRegistration;
    }

    /**
     * @param bool $approvedAfterRegistration
     */
    public function setApprovedAfterRegistration($approvedAfterRegistration)
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }

    /**
     * @return \DateTime
     */
    public function getRegisterableFrom()
    {
        return $this->registerableFrom;
    }

    /**
     * @param \DateTime $registerableFrom
     */
    public function setRegisterableFrom($registerableFrom)
    {
        $this->registerableFrom = $registerableFrom;
    }

    /**
     * @return \DateTime
     */
    public function getRegisterableTo()
    {
        return $this->registerableTo;
    }

    /**
     * @param \DateTime $registerableTo
     */
    public function setRegisterableTo($registerableTo)
    {
        $this->registerableTo = $registerableTo;
    }

    /**
     * @return int
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param int $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * @return bool
     */
    public function isDisplayArrivalDeparture()
    {
        return $this->displayArrivalDeparture;
    }

    /**
     * @param bool $displayArrivalDeparture
     */
    public function setDisplayArrivalDeparture($displayArrivalDeparture)
    {
        $this->displayArrivalDeparture = $displayArrivalDeparture;
    }

    /**
     * @return bool
     */
    public function isSyncedWithSkautIS()
    {
        return $this->syncedWithSkautIS;
    }

    /**
     * @param bool $syncedWithSkautIS
     */
    public function setSyncedWithSkautIS($syncedWithSkautIS)
    {
        $this->syncedWithSkautIS = $syncedWithSkautIS;
    }

    /**
     * @return ArrayCollection
     */
    public function getIncompatibleRoles()
    {
        return $this->incompatibleRoles;
    }

    /**
     * @param ArrayCollection $incompatibleRoles
     */
    public function setIncompatibleRoles($incompatibleRoles)
    {
        $this->incompatibleRoles = $incompatibleRoles;
    }

    /**
     * @return ArrayCollection
     */
    public function getRequiredByRole()
    {
        return $this->requiredByRole;
    }

    /**
     * @param ArrayCollection $requiredByRole
     */
    public function setRequiredByRole($requiredByRole)
    {
        $this->requiredByRole = $requiredByRole;
    }

    /**
     * @return ArrayCollection
     */
    public function getRequiredRoles()
    {
        return $this->requiredRoles;
    }

    /**
     * @param ArrayCollection $requiredRoles
     */
    public function setRequiredRoles($requiredRoles)
    {
        $this->requiredRoles = $requiredRoles;
    }

    /**
     * @return ArrayCollection
     */
    public function getRegisterableCategories()
    {
        return $this->registerableCategories;
    }

    /**
     * @param ArrayCollection $registerableCategories
     */
    public function setRegisterableCategories($registerableCategories)
    {
        $this->registerableCategories = $registerableCategories;
    }
}