<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property string $name
 * @property mixed $users
 * @property \SRS\Model\Role $parent
 * @property mixed $children
 * @property bool $standAlone
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
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $standAlone = true;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\User", mappedBy="roles")
     * @var mixed
     */
    protected $users;


    /**
     * @ORM\OneToMany(targetEntity="\SRS\Model\Role", mappedBy="parent")
     */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\Role", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;


    /**
     * @param string $name
     * @param \SRS\Model\Role $parent
     * @param bool $standAlone
     */
    public function __construct($name, $parent = NULL, $standAlone = true) {
        $this->name = $name;
        $this->standAlone = $standAlone;
        $this->parent = $parent;
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function setStandAlone($standAlone)
    {
        $this->standAlone = $standAlone;
    }

    public function getStandAlone()
    {
        return $this->standAlone;
    }

    public function setUsers($users)
    {
        $this->users = $users;
    }

    public function getUsers()
    {
        return $this->users;
    }



}
