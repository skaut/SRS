<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\ACL\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use function implode;

/**
 * Entita kategorie programového bloku.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="CategoryRepository")
 * @ORM\Table(name="category")
 */
class Category
{
    use Identifier;

    /**
     * Název kategorie.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Role, které si mohou přihlašovat programy z kategorie.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="registerableCategories", cascade={"persist"})
     * @var Collection|Role[]
     */
    protected $registerableRoles;

    /**
     * Bloky v kategorii.
     * @ORM\OneToMany(targetEntity="Block", mappedBy="category", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection|Block[]
     */
    protected $blocks;


    public function __construct()
    {
        $this->registerableRoles = new ArrayCollection();
        $this->blocks            = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRegisterableRoles() : Collection
    {
        return $this->registerableRoles;
    }

    public function getRegisterableRolesText() : string
    {
        return implode(', ', $this->registerableRoles->map(function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @param Collection|Role[] $registerableRoles
     */
    public function setRegisterableRoles(Collection $registerableRoles) : void
    {
        $this->registerableRoles->clear();
        foreach ($registerableRoles as $registerableRole) {
            $this->registerableRoles->add($registerableRole);
        }
    }

    public function addRole(Role $role) : void
    {
        if ($this->registerableRoles->contains($role)) {
            return;
        }

        $this->registerableRoles->add($role);
    }

    /**
     * @return Collection|Block[]
     */
    public function getBlocks() : Collection
    {
        return $this->blocks;
    }
}
