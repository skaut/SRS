<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Acl\Role;
use App\Model\Cms\Dto\PageDto;
use App\Model\Cms\Exceptions\PageException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use function array_map;
use function implode;
use function in_array;

/**
 * Entita stránky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Pořadí v menu.
     */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    /**
     * Viditelná.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $public = false;

    /**
     * Role, které mají na stránku přístup.
     *
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: '\App\Model\Acl\Role', inversedBy: 'pages', cascade: ['persist'])]
    protected Collection $roles;

    /**
     * Obsahy na stránce.
     *
     * @var Collection<int, Content>
     */
    #[ORM\OneToMany(targetEntity: Content::class, mappedBy: 'page', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $contents;

    /**
     * @param string $name Název stránky
     * @param string $slug Cesta stránky
     */
    public function __construct(
        #[ORM\Column(type: 'string')]
        protected string $name,
        #[ORM\Column(type: 'string', unique: true)]
        protected string $slug
    ) {
        $this->roles    = new ArrayCollection();
        $this->contents = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getRolesText(): string
    {
        return implode(', ', $this->roles->map(static fn (Role $role) => $role->getName())->toArray());
    }

    /**
     * @param Collection<int, Role> $roles
     */
    public function setRoles(Collection $roles): void
    {
        foreach ($this->roles as $role) {
            $this->removeRole($role);
        }

        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function addRole(Role $role): void
    {
        if (! $this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addPage($this);
        }
    }

    public function removeRole(Role $role): void
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removePage($this);
        }
    }

    /**
     * Vrací obsahy v oblasti.
     *
     * @return Collection<int, Content>
     *
     * @throws PageException
     */
    public function getContents(?string $area = null): Collection
    {
        if ($area === null) {
            return $this->contents;
        }

        if (! in_array($area, Content::$areas)) {
            throw new PageException('Area ' . $area . ' not defined.');
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area))
            ->orderBy(['position' => 'ASC']);

        return $this->contents->matching($criteria);
    }

    public function addContent(Content $content): void
    {
        if (! $this->contents->contains($content)) {
            $this->contents->add($content);
        }
    }

    /**
     * @throws PageException
     */
    public function convertToDto(): PageDto
    {
        $allowedRoles = array_map(static fn (Role $role) => $role->getName(), $this->roles->toArray());

        $mainContents = [];
        foreach ($this->getContents(Content::MAIN)->toArray() as $content) {
            $mainContents[] = $content->convertToDto();
        }

        $sidebarContents = [];
        foreach ($this->getContents(Content::SIDEBAR)->toArray() as $content) {
            $sidebarContents[] = $content->convertToDto();
        }

        return new PageDto($this->name, $this->slug, $allowedRoles, $mainContents, $sidebarContents, ! empty($sidebarContents));
    }
}
