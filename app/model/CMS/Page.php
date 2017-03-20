<?php

namespace App\Model\CMS;

use App\Model\ACL\Role;
use App\Model\CMS\Content\Content;
use App\Model\Page\PageException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="PageRepository")
 * @ORM\Table(name="page")
 */
class Page
{
    use Identifier;

    /**
     * Název stránky.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Cesta stránky.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $slug;

    /**
     * Pořadí v menu.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * Viditelná.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;

    /**
     * Role, které mají na stránku přístup.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", inversedBy="pages", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * Obsahy na stránce.
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
        $this->roles = new ArrayCollection();
        $this->contents = new ArrayCollection();
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
    public function addRole($role)
    {
        $this->roles->add($role);
    }

    /**
     * Vrací obsahy v oblasti.
     * @param null $area
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     * @throws PageException
     */
    public function getContents($area = null)
    {
        if ($area === null)
            return $this->contents;
        if (!in_array($area, Content::$areas))
            throw new PageException("Area {$area} not defined.");
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area))
            ->orderBy(['position' => 'ASC']);
        return $this->contents->matching($criteria);
    }

    /**
     * Má stránka nějaký obsah v oblasti?
     * @param $area
     * @return bool
     * @throws PageException
     */
    public function hasContents($area)
    {
        if (!in_array($area, Content::$areas))
            throw new PageException("Area {$area} not defined.");
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area));
        return !$this->contents->matching($criteria)->isEmpty();
    }

    /**
     * @param $content
     */
    public function addContent($content)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $content->getArea()));
        $content->setPosition($this->contents->matching($criteria)->count() + 1);
        $this->contents->add($content);
    }

    /**
     * Je stránka viditelná pro uživatele v rolích?
     * @param $roleNames
     * @return bool
     */
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