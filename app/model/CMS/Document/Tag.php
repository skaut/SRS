<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita tagu pro dokumenty.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="TagRepository")
 * @ORM\Table(name="tag")
 */
class Tag
{
    use Identifier;

    /**
     * Dokumenty s tagem.
     * @ORM\ManyToMany(targetEntity="Document", mappedBy="tags", cascade={"persist"})
     * @var Collection
     */
    protected $documents;

    /**
     * Název tagu.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

	/**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="tags")
     * @var Collection
     */
	protected $roles;

	/**
     * Tag constructor.
     */
    public function __construct()
    {
        $this->documents = new ArrayCollection();
		$this->roles = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getDocuments()
    {
        return $this->documents;
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
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRegisterableRoles($roles)
    {
        $this->roles->clear();
        foreach ($roles as $role)
            $this->roles->add($role);
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        if (!$this->roles->contains($role))
            $this->roles->add($role);
    }
}
