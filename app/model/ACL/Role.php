<?php

namespace App\Model\ACL;

use App\Model\CMS\Page;
use App\Model\Program\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita role.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="RoleRepository")
 * @ORM\Table(name="role")
 */
class Role
{
    /**
     * Role nepřihlášeného uživatele.
     */
    const GUEST = 'guest';

    /**
     * Role uživatele nepřihlášeného na seminář.
     */
    const NONREGISTERED = 'nonregistered';

    /**
     * Role neschváleného uživatele.
     */
    const UNAPPROVED = 'unapproved';

    /**
     * Role účastníka.
     */
    const ATTENDEE = 'attendee';

    /**
     * Role servis týmu.
     */
    const SERVICE_TEAM = 'service_team';

    /**
     * Role lektora.
     */
    const LECTOR = 'lector';

    /**
     * Role organizátora.
     */
    const ORGANIZER = 'organizer';

    /**
     * Role administrátora.
     */
    const ADMIN = 'admin';

    /**
     * Role, která je uživateli nastavena při testování jiné role.
     */
    const TEST = 'test';

    public static $roles = [
        self::GUEST,
        self::NONREGISTERED,
        self::UNAPPROVED,
        self::ATTENDEE,
        self::SERVICE_TEAM,
        self::LECTOR,
        self::ORGANIZER,
        self::ADMIN
    ];

    use Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @var string
     */
    protected $systemName;

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
     *
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     *
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $registerable = true;

    /**
     * Je role po registraci rovnou schvalena?
     *
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
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $fee = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $displayArrivalDeparture = false;

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
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->pages = new ArrayCollection();
        $this->incompatibleRoles = new ArrayCollection();
        $this->requiredByRole = new ArrayCollection();
        $this->requiredRoles = new ArrayCollection();
        $this->registerableCategories = new ArrayCollection();
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
     * @return string
     */
    public function getSystemName()
    {
        return $this->systemName;
    }

    /**
     * @param string $systemName
     */
    public function setSystemName($systemName)
    {
        $this->systemName = $systemName;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
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
        foreach ($this->getPages() as $page) {
            if (!$pages->contains($page))
                $page->getRoles()->removeElement($this);
        }
        foreach ($pages as $page) {
            if (!$page->getRoles()->contains($this))
                $page->getRoles()->add($this);
        }
        $this->pages = $pages;
    }

    public function addPage(Page $page)
    {
        if (!$this->pages->contains($page))
            $page->addRole($this);
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
     * Vrací true, pokud je role v tuto chvíli registrovatelná.
     * @return bool
     */
    public function isRegisterableNow()
    {
        $now = new \DateTime();
        if ($this->registerable &&
            ($this->registerableFrom == null || $this->registerableFrom <= $now) &&
            ($this->registerableTo == null || $this->registerableTo >= $now)
        )
            return true;
        return false;
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

    public function hasLimitedCapacity()
    {
        return $this->capacity !== null;
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
        foreach ($this->getIncompatibleRoles() as $role) {
            if (!$incompatibleRoles->contains($role))
                $role->getIncompatibleRoles()->removeElement($this);
        }
        foreach ($incompatibleRoles as $role) {
            if (!$role->getIncompatibleRoles()->contains($this))
                $role->getIncompatibleRoles()->add($this);
        }

        $this->incompatibleRoles = $incompatibleRoles;
    }

    /**
     * @param $role
     */
    public function addIncompatibleRole($role)
    {
        if (!$this->incompatibleRoles->contains($role))
            $this->incompatibleRoles->add($role);
    }

    /**
     * @return ArrayCollection
     */
    public function getRequiredByRole()
    {
        return $this->requiredByRole;
    }

    /**
     * Vrací všechny (tranzitivně) role, kterými je tato role vyžadována.
     * @return array
     */
    public function getRequiredByRoleTransitive()
    {
        $allRequiredByRole = array();
        foreach ($this->requiredByRole as $requiredByRole) {
            $this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
        }
        return $allRequiredByRole;
    }

    private function getRequiredByRoleTransitiveRec(&$allRequiredByRole, $role)
    {
        if ($this == $role || in_array($role, $allRequiredByRole))
            return;

        $allRequiredByRole[] = $role;

        foreach ($role->requiredByRole as $requiredByRole) {
            $this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
        }
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
     * @param $role
     */
    public function addRequiredRole($role)
    {
        if (!$this->requiredRoles->contains($role))
            $this->requiredRoles->add($role);
    }

    /**
     * Vrací všechny (tranzitivně) vyžadované role.
     * @return array
     */
    public function getRequiredRolesTransitive()
    {
        $allRequiredRoles = [];
        foreach ($this->requiredRoles as $requiredRole) {
            $this->getRequiredRolesTransitiveRec($allRequiredRoles, $requiredRole);
        }
        return $allRequiredRoles;
    }

    private function getRequiredRolesTransitiveRec(&$allRequiredRoles, $role)
    {
        if ($this == $role || in_array($role, $allRequiredRoles))
            return;

        $allRequiredRoles[] = $role;

        foreach ($role->requiredRoles as $requiredRole) {
            $this->getRequiredRolesTransitiveRec($allRequiredRoles, $requiredRole);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getRegisterableCategories()
    {
        return $this->registerableCategories;
    }

//    //nefunguje z inverse side, zatim neni potreba
//    /**
//     * @param ArrayCollection $registerableCategories
//     */
//    public function setRegisterableCategories($registerableCategories)
//    {
//        $this->registerableCategories = $registerableCategories;
//    }

    public function addRegisterableCategory(Category $category)
    {
        if (!$this->registerableCategories->contains($category))
            $category->addRole($this);
    }

    /**
     * @return string
     */
    public function getRedirectAfterLogin()
    {
        return $this->redirectAfterLogin;
    }

    /**
     * @param string $redirectAfterLogin
     */
    public function setRedirectAfterLogin($redirectAfterLogin)
    {
        $this->redirectAfterLogin = $redirectAfterLogin;
    }
}