<?php

declare(strict_types=1);

namespace App\Model\Acl;

use App\Model\Cms\Page;
use App\Model\Cms\Tag;
use App\Model\Program\Category;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

use function implode;

/**
 * Entita role.
 *
 * @ORM\Entity
 * @ORM\Table(name="role")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Role
{
    /**
     * Role nepřihlášeného uživatele.
     */
    public const GUEST = 'guest';

    /**
     * Role uživatele nepřihlášeného na seminář.
     */
    public const NONREGISTERED = 'nonregistered';

    /**
     * Role neschváleného uživatele.
     */
    public const UNAPPROVED = 'unapproved';

    /**
     * Role účastníka.
     */
    public const ATTENDEE = 'attendee';

    /**
     * Role servis týmu.
     */
    public const SERVICE_TEAM = 'service_team';

    /**
     * Role lektora.
     */
    public const LECTOR = 'lector';

    /**
     * Role organizátora.
     */
    public const ORGANIZER = 'organizer';

    /**
     * Role administrátora.
     */
    public const ADMIN = 'admin';

    /**
     * Role, která je uživateli nastavena při testování jiné role.
     */
    public const TEST = 'test';

    /** @var string[] */
    public static array $roles = [
        self::GUEST,
        self::NONREGISTERED,
        self::UNAPPROVED,
        self::ATTENDEE,
        self::SERVICE_TEAM,
        self::LECTOR,
        self::ORGANIZER,
        self::ADMIN,
    ];
    use Id;

    /**
     * Název role.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Systémový název systémové role.
     *
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    protected ?string $systemName = null;

    /**
     * Uživatelé v roli.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="roles", cascade={"persist"})
     *
     * @var Collection<User>
     */
    protected Collection $users;

    /**
     * Oprávnění role.
     *
     * @ORM\ManyToMany(targetEntity="Permission", inversedBy="roles", cascade={"persist"})
     *
     * @var Collection<Permission>
     */
    protected Collection $permissions;

    /**
     * Stránky, ke kterým má role přístup.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Cms\Page", mappedBy="roles", cascade={"persist"})
     *
     * @var Collection<Page>
     */
    protected Collection $pages;

    /**
     * Systémová role. Systémovou roli nelze odstranit.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $systemRole = true;

    /**
     * Registrovatelná role. Lze vybrat v přihlášce.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $registerable = true;

    /**
     * Automaticky schválit. Role nevyžaduje schválení registrace organizátory.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $approvedAfterRegistration = false;

    /**
     * Registrovatelná od.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $registerableFrom = null;

    /**
     * Registrovatelná do.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $registerableTo = null;

    /**
     * Kapacita.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $capacity = null;

    /**
     * Obsazenost.
     * Bude se používat pro kontrolu kapacity.
     *
     * @ORM\Column(type="integer")
     */
    protected int $occupancy = 0;

    /**
     * Poplatek.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $fee = 0;

    /**
     * Minimální věk.
     *
     * @ORM\Column(type="integer")
     */
    protected int $minimumAge = 0;

    /**
     * Synchronizovat účastníky v roli se skautIS.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $syncedWithSkautIS = true;

    /**
     * Role neregistrovatelné současně s touto rolí.
     *
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="role_role_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     *
     * @var Collection<Role>
     */
    protected Collection $incompatibleRoles;

    /**
     * Role vyžadující tuto roli.
     *
     * @ORM\ManyToMany(targetEntity="Role", mappedBy="requiredRoles", cascade={"persist"})
     *
     * @var Collection<Role>
     */
    protected Collection $requiredByRole;

    /**
     * Role vyžadované touto rolí.
     *
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="requiredByRole")
     * @ORM\JoinTable(name="role_role_required",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_role_id", referencedColumnName="id")}
     *      )
     *
     * @var Collection<Role>
     */
    protected Collection $requiredRoles;

    /**
     * Kategorie programů, na které se mohou účastníci v roli přihlásit.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Program\Category", mappedBy="registerableRoles", cascade={"persist"})
     *
     * @var Collection<Category>
     */
    protected Collection $registerableCategories;

    /**
     * Adresa, na kterou budou uživatelé v roli přesměrováni po přihlášení.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $redirectAfterLogin = null;

    /**
     * Kategorie dokumentů, ke kterým má role přístup.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Cms\Tag", mappedBy="roles", cascade={"persist"})
     *
     * @var Collection<Tag>
     */
    protected Collection $tags;

    public function __construct(string $name)
    {
        $this->name                   = $name;
        $this->users                  = new ArrayCollection();
        $this->permissions            = new ArrayCollection();
        $this->pages                  = new ArrayCollection();
        $this->incompatibleRoles      = new ArrayCollection();
        $this->requiredByRole         = new ArrayCollection();
        $this->requiredRoles          = new ArrayCollection();
        $this->registerableCategories = new ArrayCollection();
        $this->tags                   = new ArrayCollection();
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
     * @return Collection<User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): void
    {
        if (! $this->users->contains($user)) {
            $this->users->add($user);
            $user->addRole($this);
        }
    }

    public function removeUser(User $user): void
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeRole($this);
        }
    }

    /**
     * @return Collection<Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @param Collection<Permission> $permissions
     */
    public function setPermissions(Collection $permissions): void
    {
        foreach ($this->permissions as $permission) {
            $this->removePermission($permission);
        }

        foreach ($permissions as $permission) {
            $this->addPermission($permission);
        }
    }

    public function addPermission(Permission $permission): void
    {
        if (! $this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->addRole($this);
        }
    }

    public function removePermission(Permission $permission): void
    {
        if ($this->permissions->contains($permission)) {
            $this->permissions->removeElement($permission);
            $permission->removeRole($this);
        }
    }

    /**
     * @return Collection<Page>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    /**
     * @param Collection<Page> $pages
     */
    public function setPages(Collection $pages): void
    {
        foreach ($this->pages as $page) {
            $this->removePage($page);
        }

        foreach ($pages as $page) {
            $this->addPage($page);
        }
    }

    public function addPage(Page $page): void
    {
        if (! $this->pages->contains($page)) {
            $this->pages->add($page);
            $page->addRole($this);
        }
    }

    public function removePage(Page $page): void
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
            $page->removeRole($this);
        }
    }

    public function isSystemRole(): bool
    {
        return $this->systemRole;
    }

    public function setSystemRole(bool $systemRole): void
    {
        $this->systemRole = $systemRole;
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
        $now = new DateTimeImmutable();

        return $this->registerable &&
            ($this->registerableFrom === null || $this->registerableFrom <= $now) &&
            ($this->registerableTo === null || $this->registerableTo >= $now);
    }

    public function isApprovedAfterRegistration(): bool
    {
        return $this->approvedAfterRegistration;
    }

    public function setApprovedAfterRegistration(bool $approvedAfterRegistration): void
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }

    public function getRegisterableFrom(): ?DateTimeImmutable
    {
        return $this->registerableFrom;
    }

    public function setRegisterableFrom(?DateTimeImmutable $registerableFrom): void
    {
        $this->registerableFrom = $registerableFrom;
    }

    public function getRegisterableTo(): ?DateTimeImmutable
    {
        return $this->registerableTo;
    }

    public function setRegisterableTo(?DateTimeImmutable $registerableTo): void
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

    public function getMinimumAge(): int
    {
        return $this->minimumAge;
    }

    public function setMinimumAge(int $age): void
    {
        $this->minimumAge = $age;
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
     * @return Collection<Role>
     */
    public function getIncompatibleRoles(): Collection
    {
        return $this->incompatibleRoles;
    }

    /**
     * @param Collection<Role> $incompatibleRoles
     */
    public function setIncompatibleRoles(Collection $incompatibleRoles): void
    {
        foreach ($this->incompatibleRoles as $role) {
            $this->removeIncompatibleRole($role);
        }

        foreach ($incompatibleRoles as $role) {
            $this->addIncompatibleRole($role);
        }
    }

    /**
     * Vrací názvy všech nekompatibilních rolí.
     */
    public function getIncompatibleRolesText(): string
    {
        return implode(', ', $this->incompatibleRoles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    public function addIncompatibleRole(Role $role): void
    {
        if (! $this->incompatibleRoles->contains($role)) {
            $this->incompatibleRoles->add($role);
            $role->addIncompatibleRole($this);
        }
    }

    public function removeIncompatibleRole(Role $role): void
    {
        if ($this->incompatibleRoles->contains($role)) {
            $this->incompatibleRoles->removeElement($role);
            $role->removeIncompatibleRole($this);
        }
    }

    /**
     * @return Collection<Role>
     */
    public function getRequiredByRole(): Collection
    {
        return $this->requiredByRole;
    }

    public function addRequiredByRole(Role $role): void
    {
        if (! $this->requiredByRole->contains($role)) {
            $this->requiredByRole->add($role);
            $role->addRequiredRole($this);
        }
    }

    public function removeRequiredByRole(Role $role): void
    {
        if ($this->requiredByRole->contains($role)) {
            $this->requiredByRole->removeElement($role);
            $role->removeRequiredRole($this);
        }
    }

    /**
     * Vrací všechny (tranzitivně) role, kterými je tato role vyžadována.
     *
     * @return Collection<Role>
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
     * @param Collection<Role> $allRequiredByRole
     */
    private function getRequiredByRoleTransitiveRec(Collection &$allRequiredByRole, Role $role): void
    {
        if ($this->getId() !== $role->getId() && ! $allRequiredByRole->contains($role)) {
            $allRequiredByRole->add($role);

            foreach ($role->requiredByRole as $requiredByRole) {
                $this->getRequiredByRoleTransitiveRec($allRequiredByRole, $requiredByRole);
            }
        }
    }

    /**
     * @return Collection<Role>
     */
    public function getRequiredRoles(): Collection
    {
        return $this->requiredRoles;
    }

    /**
     * @param Collection<Role> $requiredRoles
     */
    public function setRequiredRoles(Collection $requiredRoles): void
    {
        foreach ($this->requiredRoles as $requiredRole) {
            $this->removeRequiredRole($requiredRole);
        }

        foreach ($requiredRoles as $requiredRole) {
            $this->addRequiredRole($requiredRole);
        }
    }

    public function addRequiredRole(Role $role): void
    {
        if (! $this->requiredRoles->contains($role)) {
            $this->requiredRoles->add($role);
            $role->addRequiredByRole($this);
        }
    }

    public function removeRequiredRole(Role $role): void
    {
        if ($this->requiredRoles->contains($role)) {
            $this->requiredRoles->removeElement($role);
            $role->removeRequiredByRole($this);
        }
    }

    /**
     * Vrací všechny (tranzitivně) vyžadované role.
     *
     * @return Collection<Role>
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
     * @param Collection<Role> $allRequiredRoles
     */
    private function getRequiredRolesTransitiveRec(Collection &$allRequiredRoles, Role $role): void
    {
        if ($this->getId() !== $role->getId() && ! $allRequiredRoles->contains($role)) {
            $allRequiredRoles->add($role);

            foreach ($role->requiredRoles as $requiredRole) {
                $this->getRequiredRolesTransitiveRec($allRequiredRoles, $requiredRole);
            }
        }
    }

    /**
     * Vrací názvy všech vyžadovaných rolí.
     */
    public function getRequiredRolesTransitiveText(): string
    {
        return implode(', ', $this->getRequiredRolesTransitive()->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @return Collection<Category>
     */
    public function getRegisterableCategories(): Collection
    {
        return $this->registerableCategories;
    }

    public function addRegisterableCategory(Category $category): void
    {
        if (! $this->registerableCategories->contains($category)) {
            $this->registerableCategories->add($category);
            $category->addRegisterableRole($this);
        }
    }

    public function removeRegisterableCategory(Category $category): void
    {
        if ($this->registerableCategories->contains($category)) {
            $this->registerableCategories->removeElement($category);
            $category->removeRegisterableRole($this);
        }
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
     * @return Collection<Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (! $this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addRole($this);
        }
    }

    public function removeTag(Tag $tag): void
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            $tag->removeRole($this);
        }
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
