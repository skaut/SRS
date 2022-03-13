<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Acl\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function implode;

/**
 * Entita tagu pro dokumenty.
 *
 * @ORM\Entity
 * @ORM\Table(name="tag")
 */
class Tag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=FALSE)
     */
    private ?int $id;

    /**
     * Dokumenty s tagem.
     *
     * @ORM\ManyToMany(targetEntity="Document", mappedBy="tags", cascade={"persist"})
     *
     * @var Collection<int, Document>
     */
    protected Collection $documents;

    /**
     * Název tagu.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Role oprávněné zobrazit dokumenty v této kategorií.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role", inversedBy="tags", cascade={"persist"})
     *
     * @var Collection<int, Role>
     */
    protected Collection $roles;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->roles     = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): void
    {
        if (! $this->documents->contains($document)) {
            $this->documents->add($document);
            $document->addTag($this);
        }
    }

    public function removeDocument(Document $document): void
    {
        if ($this->documents->contains($document)) {
            $this->documents->removeElement($document);
            $document->removeTag($this);
        }
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
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getRolesText(): string
    {
        return implode(', ', $this->roles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
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
            $role->addTag($this);
        }
    }

    public function removeRole(Role $role): void
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            $role->removeTag($this);
        }
    }
}
