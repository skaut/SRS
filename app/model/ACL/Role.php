<?php

namespace App\Model\ACL;

use App\Model\CMS\Page;
use App\Model\Program\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
     * Název role.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Systémový název systémové role.
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @var string
     */
    protected $systemName;

    /**
     * Uživatelé v roli.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="roles", cascade={"persist"})
     * @var Collection
     */
    protected $users;

    /**
     * Oprávnění role.
     * @ORM\ManyToMany(targetEntity="Permission", inversedBy="roles")
     * @var Collection
     */
    protected $permissions;

    /**
     * Stránky, ke kterým má role přístup.
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Page", mappedBy="roles", cascade={"persist"})
     * @var Collection
     */
    protected $pages;

    /**
     * Systémová role. Systémovou roli nelze odstranit.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $system = TRUE;

    /**
     * Registrovatelná role. Lze vybrat v přihlášce.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $registerable = TRUE;

    /**
     * Automaticky schválit. Role nevyžaduje schválení registrace organizátory.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $approvedAfterRegistration = FALSE;

    /**
     * Registrovatelná od.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $registerableFrom;

    /**
     * Registrovatelná do.
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $registerableTo;

    /**
     * Kapacita.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * Poplatek.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $fee = 0;

    /**
     * Evidovat příjezd a odjezd.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $displayArrivalDeparture = FALSE;

    /**
     * Synchronizovat účastníky v roli se skautIS.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $syncedWithSkautIS = TRUE;

    /**
     * Role neregistrovatelné současně s touto rolí.
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="role_role_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     * @var Collection
     */
    protected $incompatibleRoles;

    /**
     * Role vyžadující tuto roli.
     * @ORM\ManyToMany(targetEntity="Role", mappedBy="requiredRoles", cascade={"persist"})
     * @var Collection
     */
    protected $requiredByRole;

    /**
     * Role vyžadované touto rolí.
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="requiredByRole")
     * @ORM\JoinTable(name="role_role_required",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
     *      )
     * @var Collection
     */
    protected $requiredRoles;

    /**
     * Kategorie programů, na které se mohou účastníci v roli přihlásit.
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Category", mappedBy="registerableRoles", cascade={"persist"})
     * @var Collection
     */
    protected $registerableCategories;

    /**
     * Adresa, na kterou budou uživatelé v roli přesměrováni po přihlášení.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $redirectAfterLogin;

	
    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\Tag", mappedBy="roles")
     */
    private $tags;
	
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSystemName(): ?string
    {
        return $this->systemName;
    }

    /**
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection
     */
    public function getApprovedUsers(): Collection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('approved', TRUE));
        return $this->users->matching($criteria);
    }

    /**
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @param Collection $permissions
     */
    public function setPermissions(Collection $permissions): void
    {
        $this->permissions->clear();
        foreach ($permissions as $permission)
            $this->permissions->add($permission);
    }

    public function addPermission(Permission $permission): void
    {
        $this->permissions->add($permission);
    }

    /**
     * @return Collection
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    /**
     * @param Collection $pages
     */
    public function setPages(Collection $pages): void
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

    /**
     * @param Page $page
     */
    public function addPage(Page $page): void
    {
        if (!$this->pages->contains($page))
            $page->addRole($this);
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->system;
    }

    /**
     * @param bool $system
     */
    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }

    /**
     * @return bool
     */
    public function isRegisterable(): bool
    {
        return $this->registerable;
    }

    /**
     * @param bool $registerable
     */
    public function setRegisterable(bool $registerable): void
    {
        $this->registerable = $registerable;
    }

    /**
     * Vrací true, pokud je role v tuto chvíli registrovatelná.
     * @return bool
     */
    public function isRegisterableNow(): bool
    {
        $now = new \DateTime();
        if ($this->registerable &&
            ($this->registerableFrom == NULL || $this->registerableFrom <= $now) &&
            ($this->registerableTo == NULL || $this->registerableTo >= $now)
        )
            return TRUE;
        return FALSE;
    }

    /**
     * @return bool
     */
    public function isApprovedAfterRegistration(): bool
    {
        return $this->approvedAfterRegistration;
    }

    /**
     * @param bool $approvedAfterRegistration
     */
    public function setApprovedAfterRegistration(bool $approvedAfterRegistration): void
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }

    /**
     * @return \DateTime
     */
    public function getRegisterableFrom(): ?\DateTime
    {
        return $this->registerableFrom;
    }

    /**
     * @param \DateTime $registerableFrom
     */
    public function setRegisterableFrom(?\DateTime $registerableFrom): void
    {
        $this->registerableFrom = $registerableFrom;
    }

    /**
     * @return \DateTime
     */
    public function getRegisterableTo(): ?\DateTime
    {
        return $this->registerableTo;
    }

    /**
     * @param \DateTime $registerableTo
     */
    public function setRegisterableTo(?\DateTime $registerableTo): void
    {
        $this->registerableTo = $registerableTo;
    }

    /**
     * @return int
     */
    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return bool
     */
    public function hasLimitedCapacity(): bool
    {
        return $this->capacity !== NULL;
    }

    /**
     * @return int
     */
    public function getFee(): ?int
    {
        return $this->fee;
    }

    /**
     * @param int $fee
     */
    public function setFee(?int $fee): void
    {
        $this->fee = $fee;
    }

    /**
     * @return bool
     */
    public function isDisplayArrivalDeparture(): bool
    {
        return $this->displayArrivalDeparture;
    }

    /**
     * @param bool $displayArrivalDeparture
     */
    public function setDisplayArrivalDeparture(bool $displayArrivalDeparture)
    {
        $this->displayArrivalDeparture = $displayArrivalDeparture;
    }

    /**
     * @return bool
     */
    public function isSyncedWithSkautIS(): bool
    {
        return $this->syncedWithSkautIS;
    }

    /**
     * @param bool $syncedWithSkautIS
     */
    public function setSyncedWithSkautIS(bool $syncedWithSkautIS): void
    {
        $this->syncedWithSkautIS = $syncedWithSkautIS;
    }

    /**
     * @return Collection|Role[]
     */
    public function getIncompatibleRoles(): Collection
    {
        return $this->incompatibleRoles;
    }

    /**
     * @param Collection $incompatibleRoles
     */
    public function setIncompatibleRoles(Collection $incompatibleRoles): void
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
     * Vrací názvy všech nekompatibilních rolí.
     */
    public function getIncompatibleRolesText(): string
    {
        $incompatibleRolesNames = [];
        foreach ($this->getIncompatibleRoles() as $incompatibleRole) {
            $incompatibleRolesNames[] = $incompatibleRole->getName();
        }
        return implode(', ', $incompatibleRolesNames);
    }

    /**
     * @param $role
     */
    public function addIncompatibleRole(Role $role): void
    {
        if (!$this->incompatibleRoles->contains($role))
            $this->incompatibleRoles->add($role);
    }

    /**
     * @return Collection|Role[]
     */
    public function getRequiredByRole(): Collection
    {
        return $this->requiredByRole;
    }

    /**
     * Vrací všechny (tranzitivně) role, kterými je tato role vyžadována.
     * @return Collection|Role[]
     */
    public function getRequiredByRoleTransitive(): Collection
    {
        $allRequiredByRole = new ArrayCollection();
        foreach ($this->requiredByRole as $requiredByRole) {
            $this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
        }
        return $allRequiredByRole;
    }

    /**
     * @param Collection $allRequiredByRole
     * @param Role $role
     */
    private function getRequiredByRoleTransitiveRec(Collection &$allRequiredByRole, Role $role): void
    {
        if ($this === $role || $allRequiredByRole->contains($role))
            return;

        $allRequiredByRole->add($role);

        foreach ($role->requiredByRole as $requiredByRole) {
            $this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
        }
    }

    /**
     * @return Collection
     */
    public function getRequiredRoles(): Collection
    {
        return $this->requiredRoles;
    }

    /**
     * @param Collection $requiredRoles
     */
    public function setRequiredRoles(Collection $requiredRoles): void
    {
        $this->requiredRoles->clear();
        foreach ($requiredRoles as $requiredRole)
            $this->requiredRoles->add($requiredRole);
    }

    /**
     * @param $role
     */
    public function addRequiredRole(Role $role): void
    {
        if (!$this->requiredRoles->contains($role))
            $this->requiredRoles->add($role);
    }

    /**
     * Vrací všechny (tranzitivně) vyžadované role.
     * @return Collection|Role[]
     */
    public function getRequiredRolesTransitive(): Collection
    {
        $allRequiredRoles = new ArrayCollection();
        foreach ($this->requiredRoles as $requiredRole) {
            $this->getRequiredRolesTransitiveRec($allRequiredRoles, $requiredRole);
        }
        return $allRequiredRoles;
    }

    /**
     * @param Collection $allRequiredRoles
     * @param Role $role
     */
    private function getRequiredRolesTransitiveRec(Collection &$allRequiredRoles, Role $role): void
    {
        if ($this === $role || $allRequiredRoles->contains($role))
            return;

        $allRequiredRoles->add($role);

        foreach ($role->requiredRoles as $requiredRole) {
            $this->getRequiredRolesTransitiveRec($allRequiredRoles, $requiredRole);
        }
    }

    /**
     * Vrací názvy všech vyžadovaných rolí.
     */
    public function getRequiredRolesTransitiveText(): string
    {
        $requiredRolesNames = [];
        foreach ($this->getRequiredRolesTransitive() as $requiredRole) {
            $requiredRolesNames[] = $requiredRole->getName();
        }
        return implode(', ', $requiredRolesNames);
    }

    /**
     * @return Collection|Category[]
     */
    public function getRegisterableCategories(): Collection
    {
        return $this->registerableCategories;
    }

    public function addRegisterableCategory(Category $category): void
    {
        if (!$this->registerableCategories->contains($category))
            $category->addRole($this);
    }

    /**
     * @return string
     */
    public function getRedirectAfterLogin(): ?string
    {
        return $this->redirectAfterLogin;
    }

    /**
     * @param string $redirectAfterLogin
     */
    public function setRedirectAfterLogin(?string $redirectAfterLogin): void
    {
        $this->redirectAfterLogin = $redirectAfterLogin;
    }

    /**
     * @return int
     */
    public function countUsers(): int
    {
        return $this->users->count();
    }

    public function countApprovedUsers(): int
    {
        return $this->getApprovedUsers()->count();
    }

    /**
     * @return int|null
     */
    public function countUnoccupied(): ?int
    {
        return $this->capacity ? $this->capacity - $this->countUsers() : NULL;
    }

    /**
     * @return string
     */
    public function getOccupancyText(): string
    {
        return $this->capacity ? $this->countUsers() . '/' . $this->capacity : $this->countUsers();
    }
}
