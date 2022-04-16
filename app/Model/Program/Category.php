<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function implode;

/**
 * Entita kategorie programového bloku.
 *
 * @ORM\Entity
 * @ORM\Table(name="category")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false)
     */
    private ?int $id = null;

    /**
     * Název kategorie.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Role, které si mohou přihlašovat programy z kategorie.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role", inversedBy="registerableCategories", cascade={"persist"})
     *
     * @var Collection<int, Role>
     */
    protected Collection $registerableRoles;

    /**
     * Bloky v kategorii.
     *
     * @ORM\OneToMany(targetEntity="Block", mappedBy="category", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     *
     * @var Collection<int, Block>
     */
    protected Collection $blocks;

    public function __construct(string $name)
    {
        $this->name              = $name;
        $this->registerableRoles = new ArrayCollection();
        $this->blocks            = new ArrayCollection();
    }

    public function __clone()
    {
        $this->registerableRoles = clone $this->registerableRoles;
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

    /**
     * @return Collection<int, Role>
     */
    public function getRegisterableRoles(): Collection
    {
        return $this->registerableRoles;
    }

    public function getRegisterableRolesText(): string
    {
        return implode(', ', $this->registerableRoles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @param Collection<int, Role> $registerableRoles
     */
    public function setRegisterableRoles(Collection $registerableRoles): void
    {
        foreach ($this->registerableRoles as $registerableRole) {
            $this->removeRegisterableRole($registerableRole);
        }

        foreach ($registerableRoles as $registerableRole) {
            $this->addRegisterableRole($registerableRole);
        }
    }

    public function addRegisterableRole(Role $role): void
    {
        if (! $this->registerableRoles->contains($role)) {
            $this->registerableRoles->add($role);
            $role->addRegisterableCategory($this);
        }
    }

    public function removeRegisterableRole(Role $role): void
    {
        if ($this->registerableRoles->contains($role)) {
            $this->registerableRoles->removeElement($role);
            $role->removeRegisterableCategory($this);
        }
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): void
    {
        if (! $this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setCategory($this);
        }
    }

    public function removeBlock(Block $block): void
    {
        if ($this->blocks->contains($block)) {
            $this->blocks->removeElement($block);
            $block->setCategory(null);
        }
    }
}
