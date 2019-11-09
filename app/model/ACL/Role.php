<?php
declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\CMS\Document\Tag;
use App\Model\CMS\Page;
use App\Model\Program\Category;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;
use function implode;

/**
 * Entita role.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="RoleRepository")
 * @ORM\Table(name="role")
 */
class Role
{

	/**
	 * Role nepřihlášeného uživatele.
	 * @var string
	 */
	public const GUEST = 'guest';

	/**
	 * Role uživatele nepřihlášeného na seminář.
	 * @var string
	 */
	public const NONREGISTERED = 'nonregistered';

	/**
	 * Role neschváleného uživatele.
	 * @var string
	 */
	public const UNAPPROVED = 'unapproved';

	/**
	 * Role účastníka.
	 * @var string
	 */
	public const ATTENDEE = 'attendee';

	/**
	 * Role servis týmu.
	 * @var string
	 */
	public const SERVICE_TEAM = 'service_team';

	/**
	 * Role lektora.
	 * @var string
	 */
	public const LECTOR = 'lector';

	/**
	 * Role organizátora.
	 * @var string
	 */
	public const ORGANIZER = 'organizer';

	/**
	 * Role administrátora.
	 * @var string
	 */
	public const ADMIN = 'admin';

	/**
	 * Role, která je uživateli nastavena při testování jiné role.
	 * @var string
	 */
	public const TEST = 'test';

	/** @var string[] */
	public static $roles = [
		self::GUEST,
		self::NONREGISTERED,
		self::UNAPPROVED,
		self::ATTENDEE,
		self::SERVICE_TEAM,
		self::LECTOR,
		self::ORGANIZER,
		self::ADMIN,
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
	 * @var Collection|User[]
	 */
	protected $users;

	/**
	 * Oprávnění role.
	 * @ORM\ManyToMany(targetEntity="Permission", inversedBy="roles", cascade={"persist"})
	 * @var Collection|Permission[]
	 */
	protected $permissions;

	/**
	 * Stránky, ke kterým má role přístup.
	 * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Page", mappedBy="roles", cascade={"persist"})
	 * @var Collection|Page[]
	 */
	protected $pages;

	/**
	 * Systémová role. Systémovou roli nelze odstranit.
	 * @ORM\Column(type="boolean")
	 * @var bool
	 */
	protected $system = true;

	/**
	 * Registrovatelná role. Lze vybrat v přihlášce.
	 * @ORM\Column(type="boolean")
	 * @var bool
	 */
	protected $registerable = true;

	/**
	 * Automaticky schválit. Role nevyžaduje schválení registrace organizátory.
	 * @ORM\Column(type="boolean")
	 * @var bool
	 */
	protected $approvedAfterRegistration = false;

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
	 * Obsazenost.
	 * Bude se používat pro kontrolu kapacity.
	 * @ORM\Column(type="integer")
	 * @var int
	 */
	protected $occupancy = 0;

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
	protected $displayArrivalDeparture = false;

	/**
	 * Synchronizovat účastníky v roli se skautIS.
	 * @ORM\Column(type="boolean")
	 * @var bool
	 */
	protected $syncedWithSkautIS = true;

	/**
	 * Role neregistrovatelné současně s touto rolí.
	 * @ORM\ManyToMany(targetEntity="Role")
	 * @ORM\JoinTable(name="role_role_incompatible",
	 *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
	 *      )
	 * @var Collection|Role[]
	 */
	protected $incompatibleRoles;

	/**
	 * Role vyžadující tuto roli.
	 * @ORM\ManyToMany(targetEntity="Role", mappedBy="requiredRoles", cascade={"persist"})
	 * @var Collection|Role[]
	 */
	protected $requiredByRole;

	/**
	 * Role vyžadované touto rolí.
	 * @ORM\ManyToMany(targetEntity="Role", inversedBy="requiredByRole")
	 * @ORM\JoinTable(name="role_role_required",
	 *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
	 *      )
	 * @var Collection|Role[]
	 */
	protected $requiredRoles;

	/**
	 * Kategorie programů, na které se mohou účastníci v roli přihlásit.
	 * @ORM\ManyToMany(targetEntity="\App\Model\Program\Category", mappedBy="registerableRoles", cascade={"persist"})
	 * @var Collection|Category[]
	 */
	protected $registerableCategories;

	/**
	 * Adresa, na kterou budou uživatelé v roli přesměrováni po přihlášení.
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	protected $redirectAfterLogin;

	/**
	 * Kategorie dokumentů, ke kterým má role přístup.
	 * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\Tag", mappedBy="roles", cascade={"persist"})
	 * @var Collection|Tag[]
	 */
	protected $tags;

	public function __construct(string $name)
	{
		$this->name = $name;
		$this->users = new ArrayCollection();
		$this->permissions = new ArrayCollection();
		$this->pages = new ArrayCollection();
		$this->incompatibleRoles = new ArrayCollection();
		$this->requiredByRole = new ArrayCollection();
		$this->requiredRoles = new ArrayCollection();
		$this->registerableCategories = new ArrayCollection();
		$this->tags = new ArrayCollection();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getSystemName(): ?string
	{
		return $this->systemName;
	}

	/**
	 * @return Collection|User[]
	 */
	public function getUsers(): Collection
	{
		return $this->users;
	}

	/**
	 * @return Collection|Permission[]
	 */
	public function getPermissions(): Collection
	{
		return $this->permissions;
	}

	/**
	 * @param Collection|Permission[] $permissions
	 */
	public function setPermissions(Collection $permissions): void
	{
		$this->permissions->clear();
		foreach ($permissions as $permission) {
			$this->permissions->add($permission);
		}
	}

	public function addPermission(Permission $permission): void
	{
		$this->permissions->add($permission);
	}

	/**
	 * @return Collection|Page[]
	 */
	public function getPages(): Collection
	{
		return $this->pages;
	}

	/**
	 * @param Collection|Page[] $pages
	 */
	public function setPages(Collection $pages): void
	{
		foreach ($this->getPages() as $page) {
			if ($pages->contains($page)) {
				continue;
			}

			$page->getRoles()->removeElement($this);
		}
		foreach ($pages as $page) {
			if ($page->getRoles()->contains($this)) {
				continue;
			}

			$page->getRoles()->add($this);
		}
		$this->pages = $pages;
	}

	public function addPage(Page $page): void
	{
		if ($this->pages->contains($page)) {
			return;
		}

		$page->addRole($this);
	}

	public function isSystem(): bool
	{
		return $this->system;
	}

	public function setSystem(bool $system): void
	{
		$this->system = $system;
	}

	public function isRegisterable(): bool
	{
		return $this->registerable;
	}

	public function setRegisterable(bool $registerable): void
	{
		$this->registerable = $registerable;
	}

	/**
	 * Vrací true, pokud je role v tuto chvíli registrovatelná.
	 */
	public function isRegisterableNow(): bool
	{
		$now = new \DateTime();
		if ($this->registerable &&
			($this->registerableFrom === null || $this->registerableFrom <= $now) &&
			($this->registerableTo === null || $this->registerableTo >= $now)
		) {
			return true;
		}
		return false;
	}

	public function isApprovedAfterRegistration(): bool
	{
		return $this->approvedAfterRegistration;
	}

	public function setApprovedAfterRegistration(bool $approvedAfterRegistration): void
	{
		$this->approvedAfterRegistration = $approvedAfterRegistration;
	}

	public function getRegisterableFrom(): ?\DateTime
	{
		return $this->registerableFrom;
	}

	public function setRegisterableFrom(?\DateTime $registerableFrom): void
	{
		$this->registerableFrom = $registerableFrom;
	}

	public function getRegisterableTo(): ?\DateTime
	{
		return $this->registerableTo;
	}

	public function setRegisterableTo(?\DateTime $registerableTo): void
	{
		$this->registerableTo = $registerableTo;
	}

	public function getCapacity(): ?int
	{
		return $this->capacity;
	}

	public function setCapacity(?int $capacity): void
	{
		$this->capacity = $capacity;
	}

	public function hasLimitedCapacity(): bool
	{
		return $this->capacity !== null;
	}

	public function getOccupancy(): int
	{
		return $this->occupancy;
	}

	public function getFee(): ?int
	{
		return $this->fee;
	}

	public function setFee(?int $fee): void
	{
		$this->fee = $fee;
	}

	public function isDisplayArrivalDeparture(): bool
	{
		return $this->displayArrivalDeparture;
	}

	public function setDisplayArrivalDeparture(bool $displayArrivalDeparture): void
	{
		$this->displayArrivalDeparture = $displayArrivalDeparture;
	}

	public function isSyncedWithSkautIS(): bool
	{
		return $this->syncedWithSkautIS;
	}

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
	 * @param Collection|Role[] $incompatibleRoles
	 */
	public function setIncompatibleRoles(Collection $incompatibleRoles): void
	{
		foreach ($this->getIncompatibleRoles() as $role) {
			if ($incompatibleRoles->contains($role)) {
				continue;
			}

			$role->getIncompatibleRoles()->removeElement($this);
		}
		foreach ($incompatibleRoles as $role) {
			if ($role->getIncompatibleRoles()->contains($this)) {
				continue;
			}

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

	public function addIncompatibleRole(Role $role): void
	{
		if ($this->incompatibleRoles->contains($role)) {
			return;
		}

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
	 * @param Collection|Role[] $allRequiredByRole
	 */
	private function getRequiredByRoleTransitiveRec(Collection &$allRequiredByRole, Role $role): void
	{
		if ($this === $role || $allRequiredByRole->contains($role)) {
			return;
		}

		$allRequiredByRole->add($role);

		foreach ($role->requiredByRole as $requiredByRole) {
			$this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
		}
	}

	/**
	 * @return Collection|Role[]
	 */
	public function getRequiredRoles(): Collection
	{
		return $this->requiredRoles;
	}

	/**
	 * @param Collection|Role[] $requiredRoles
	 */
	public function setRequiredRoles(Collection $requiredRoles): void
	{
		$this->requiredRoles->clear();
		foreach ($requiredRoles as $requiredRole) {
			$this->requiredRoles->add($requiredRole);
		}
	}

	public function addRequiredRole(Role $role): void
	{
		if ($this->requiredRoles->contains($role)) {
			return;
		}

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
	 * @param Collection|Role[] $allRequiredRoles
	 */
	private function getRequiredRolesTransitiveRec(Collection &$allRequiredRoles, Role $role): void
	{
		if ($this === $role || $allRequiredRoles->contains($role)) {
			return;
		}

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
		if ($this->registerableCategories->contains($category)) {
			return;
		}

		$category->addRole($this);
	}

	public function getRedirectAfterLogin(): ?string
	{
		return $this->redirectAfterLogin;
	}

	public function setRedirectAfterLogin(?string $redirectAfterLogin): void
	{
		$this->redirectAfterLogin = $redirectAfterLogin;
	}

	/**
	 * @return Collection|Tag[]
	 */
	public function getTags(): Collection
	{
		return $this->tags;
	}

	public function countUsers(): int
	{
		return $this->users->count();
	}

	public function countUnoccupied(): ?int
	{
		return $this->capacity ? $this->capacity - $this->countUsers() : null;
	}

	public function getOccupancyText(): string
	{
		return $this->capacity ? $this->countUsers() . '/' . $this->capacity : '' . $this->countUsers();
	}
}
