<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\Acl;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $name
 * @property bool $system
 * @property bool $registerable
 * @property \Doctrine\Common\Collections\ArrayCollection $users
 * @property \SRS\Model\Acl\Role $parent
 * @property \Doctrine\Common\Collections\ArrayCollection $permissions
 * @property mixed $children
 */
class Role extends \Nette\Object
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $name;


    /**
     * @ORM\OneToMany(targetEntity="\SRS\model\User", mappedBy="role")
     * @var mixed
     */
    protected $users;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Permission", inversedBy="roles", cascade={"persist"})
     * @var mixed
     */
    protected $permissions;


    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\Acl\Role", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Role", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;




    /**
     * Pokud je role systemova, nelze ji mazat
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $system = true;

    /**
     * Lze o tuto roli zazadat pri registraci na seminar?
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $registerable = true;

    /**
     * @param string $name
     * @param \SRS\Model\Acl\Role $parent
     * @param bool $standAlone
     */
    public function __construct($name, $parent = NULL) {
        $this->name = $name;
        $this->parent = $parent;
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUsers($users)
    {
        $this->users = $users;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function getPermissions()
    {
       return $this->permissions;
    }

    public function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    public function setSystem($system)
    {
        $this->system = $system;
    }

    public function isSystem()
    {
        return $this->system;
    }

    public function getRegisterable()
    {
        return $this->registerable;
    }

    public function setRegisterable($registerable)
    {
        $this->registerable = $registerable;
    }

    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }



}
