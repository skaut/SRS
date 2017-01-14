<?php

namespace App\Model\CMS;

use App\Model\CMS\Content\Content;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="PageRepository")
 * @ORM\Table(name="page")
 */
class Page
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $slug;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="pages", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\CMS\Content\Content", mappedBy="page", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection
     */
    protected $contents;

    /**
     * Page constructor.
     * @param string $name
     * @param string $slug
     */
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
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param bool $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param ArrayCollection $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param Role $role
     */
    public function addRole($role) {
        $this->roles->add($role);
    }

    public function getContents($area = null)
    {
        if ($area === null)
            return $this->contents;
        if (!in_array($area, Content::$areas))
            throw new SRSPageException("Area {$area} not defined.");
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area))
            ->orderBy(['position' => 'ASC']);
        return $this->contents->matching($criteria);
    }

    public function hasContents($area)
    {
        if (!in_array($area, Content::$areas))
            throw new SRSPageException("Area {$area} not defined.");
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area));
        return !$this->contents->matching($criteria)->isEmpty();
    }

    /**
     * @param ArrayCollection $contents
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    public function isAllowedForRoles($roleNames)
    {
        foreach ($roleNames as $roleName) {
            foreach ($this->roles as $role) {
                if ($roleName == $role->getName())
                    return true;
            }
        }
        return false;
    }
}