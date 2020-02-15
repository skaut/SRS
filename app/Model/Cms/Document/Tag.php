<?php

declare(strict_types=1);

namespace App\Model\Cms\Document;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;
use function implode;

/**
 * Entita tagu pro dokumenty.
 *
 * @ORM\Entity(repositoryClass="TagRepository")
 * @ORM\Table(name="tag")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Tag
{
    use Id;

    /**
     * Dokumenty s tagem.
     *
     * @ORM\ManyToMany(targetEntity="Document", mappedBy="tags", cascade={"persist"})
     *
     * @var Collection|Document[]
     */
    protected $documents;

    /**
     * Název tagu.
     *
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected $name;

    /**
     * Role oprávněné zobrazit dokumenty v této kategorií.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role", inversedBy="tags", cascade={"persist"})
     *
     * @var Collection|Role[]
     */
    protected $roles;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->roles     = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments() : Collection
    {
        return $this->documents;
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
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    public function getRolesText() : string
    {
        return implode(', ', $this->roles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @param Collection|Role[] $roles
     */
    public function setRoles(Collection $roles) : void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }

    public function addRole(Role $role) : void
    {
        if (! $this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }
}
