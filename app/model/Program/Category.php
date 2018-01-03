<?php

namespace App\Model\Program;

use App\Model\ACL\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


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
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="registerableCategories")
     * @var Collection
     */
    protected $registerableRoles;

    /**
     * Bloky v kategorii.
     * @ORM\OneToMany(targetEntity="Block", mappedBy="category", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    protected $blocks;


    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->registerableRoles = new ArrayCollection();
        $this->blocks = new ArrayCollection();
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
     * @return Collection
     */
    public function getRegisterableRoles()
    {
        return $this->registerableRoles;
    }

    /**
     * @param Collection $registerableRoles
     */
    public function setRegisterableRoles($registerableRoles) //TODO kontrola
    {
        $this->registerableRoles = $registerableRoles;
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        if (!$this->registerableRoles->contains($role))
            $this->registerableRoles->add($role);
    }

    /**
     * @return Collection
     */
    public function getBlocks()
    {
        return $this->blocks;
    }
}
