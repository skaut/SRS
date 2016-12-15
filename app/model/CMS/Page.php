<?php

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="PageRepository")
 * @ORM\Table(name="page")
 */
class Page
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string") */
    protected $name;

    /** @ORM\Column(type="string", unique=true) */
    protected $slug;

    /** @ORM\Column(type="integer") */
    protected $position = 0;

    /** @ORM\Column(type="boolean") */
    protected $public = false;

    /** @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="pages", cascade={"persist", "remove"}) */
    protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\CMS\Content\Content", mappedBy="page", cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $contents;

    public function __construct($name, $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contents = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param mixed $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param mixed $contents
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
    }
}