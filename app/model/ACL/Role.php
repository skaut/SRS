<?php

namespace App\Model\ACL;

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
    const ATTENDEE = 'attentee';
    const SERVICE_TEAM = 'sevice_team';
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

    /** @ORM\Column(type="string" unique=true) */
    protected $name;

    /** @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="roles", cascade={"persist", "remove"}) */
    protected $users;

    /** @ORM\ManyToMany(targetEntity="Permission", inversedBy="roles", cascade={"persist", "remove"}) */
    protected $permissions;

    /** @ORM\ManyToMany(targetEntity="\App\Model\CMS\Page", mappedBy="roles", cascade={"persist", "remove"}) */
    protected $pages;

    /**
     * Pokud je role systemova, nelze ji smazat
     * @ORM\Column(type="boolean")
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     * @ORM\Column(type="boolean")
     */
    protected $registerable = true;

    /**
     * Je role po registraci rovnou schvalena?
     * @ORM\Column(type="boolean")
     */
    protected $approvedAfterRegistration = false;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $registerableFrom;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $registerableTo;

    /**
     * Maximální počet osob v roli
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $capacity;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $fee;

    /** @ORM\Column(type="boolean", nullable=true) */
    protected $displayArrivalDeparture;

    /** @ORM\Column(type="boolean") */
    protected $syncedWithSkautIS = true;

    /**
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="role_role_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     */
    protected $incompatibleRoles;

    /** @ORM\ManyToMany(targetEntity="Role", mappedBy="requiredRoles", cascade={"persist", "remove"}) */
    protected $requiredByRole;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="requiredByRole", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="role_role_required",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
     *      )
     */
    protected $requiredRoles;

    /** @ORM\ManyToMany(targetEntity="\App\Model\Program\Category", mappedBy="registerableRoles", cascade={"persist", "remove"}) */
    protected $registerableCategories;

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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public static function getRoles()
    {
        return self::$roles;
    }

    /**
     * @param array $roles
     */
    public static function setRoles($roles)
    {
        self::$roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return mixed
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param mixed $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param mixed $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return mixed
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @param mixed $system
     */
    public function setSystem($system)
    {
        $this->system = $system;
    }

    /**
     * @return mixed
     */
    public function getRegisterable()
    {
        return $this->registerable;
    }

    /**
     * @param mixed $registerable
     */
    public function setRegisterable($registerable)
    {
        $this->registerable = $registerable;
    }

    /**
     * @return mixed
     */
    public function getApprovedAfterRegistration()
    {
        return $this->approvedAfterRegistration;
    }

    /**
     * @param mixed $approvedAfterRegistration
     */
    public function setApprovedAfterRegistration($approvedAfterRegistration)
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }

    /**
     * @return mixed
     */
    public function getRegisterableFrom()
    {
        return $this->registerableFrom;
    }

    /**
     * @param mixed $registerableFrom
     */
    public function setRegisterableFrom($registerableFrom)
    {
        $this->registerableFrom = $registerableFrom;
    }

    /**
     * @return mixed
     */
    public function getRegisterableTo()
    {
        return $this->registerableTo;
    }

    /**
     * @param mixed $registerableTo
     */
    public function setRegisterableTo($registerableTo)
    {
        $this->registerableTo = $registerableTo;
    }

    /**
     * @return mixed
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @param mixed $capacity
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return mixed
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param mixed $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * @return mixed
     */
    public function getDisplayArrivalDeparture()
    {
        return $this->displayArrivalDeparture;
    }

    /**
     * @param mixed $displayArrivalDeparture
     */
    public function setDisplayArrivalDeparture($displayArrivalDeparture)
    {
        $this->displayArrivalDeparture = $displayArrivalDeparture;
    }

    /**
     * @return mixed
     */
    public function getSyncedWithSkautIS()
    {
        return $this->syncedWithSkautIS;
    }

    /**
     * @param mixed $syncedWithSkautIS
     */
    public function setSyncedWithSkautIS($syncedWithSkautIS)
    {
        $this->syncedWithSkautIS = $syncedWithSkautIS;
    }

    /**
     * @return mixed
     */
    public function getIncompatibleRoles()
    {
        return $this->incompatibleRoles;
    }

    /**
     * @param mixed $incompatibleRoles
     */
    public function setIncompatibleRoles($incompatibleRoles)
    {
        $this->incompatibleRoles = $incompatibleRoles;
    }

    /**
     * @return mixed
     */
    public function getRequiredByRole()
    {
        return $this->requiredByRole;
    }

    /**
     * @param mixed $requiredByRole
     */
    public function setRequiredByRole($requiredByRole)
    {
        $this->requiredByRole = $requiredByRole;
    }

    /**
     * @return mixed
     */
    public function getRequiredRoles()
    {
        return $this->requiredRoles;
    }

    /**
     * @param mixed $requiredRoles
     */
    public function setRequiredRoles($requiredRoles)
    {
        $this->requiredRoles = $requiredRoles;
    }

    /**
     * @return mixed
     */
    public function getRegisterableCategories()
    {
        return $this->registerableCategories;
    }

    /**
     * @param mixed $registerableCategories
     */
    public function setRegisterableCategories($registerableCategories)
    {
        $this->registerableCategories = $registerableCategories;
    }


}