<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;
use function implode;

/**
 * Entita kategorie programového bloku.
 *
 * @ORM\Entity(repositoryClass="CategoryRepository")
 * @ORM\Table(name="category")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Category
{
    use Id;

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
     * @var Collection|Role[]
     */
    protected Collection $registerableRoles;

    /**
     * Bloky v kategorii.
     *
     * @ORM\OneToMany(targetEntity="Block", mappedBy="category", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     *
     * @var Collection|Block[]
     */
    protected Collection $blocks;

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
        return implode(', ', $this->registerableRoles->map(static function (Role $role) {
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
        if (! $this->registerableRoles->contains($role)) {
            $this->registerableRoles->add($role);
        }
    }

    /**
     * @return Collection|Block[]
     */
    public function getBlocks() : Collection
    {
        return $this->blocks;
    }
}
