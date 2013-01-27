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
use Doctrine\Common\Collections\Criteria;


/**
 * @ORM\Entity(repositoryClass="\SRS\Model\Acl\RoleRepository")
 *
 * @property-read int $id
 * @property string $name
 * @property bool $system
 * @property bool $registerable
 * @property \DateTime|string $registerableFrom
 * @property \DateTime|string $registerableTo
 * @property \Doctrine\Common\Collections\ArrayCollection $users
 * @property \Doctrine\Common\Collections\ArrayCollection $permissions
 */
class Role extends \SRS\Model\BaseEntity
{

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
     * @ORM\ManyToMany(targetEntity="\SRS\model\CMS\Page", inversedBy="roles", cascade={"persist"})
     * @var mixed
     */
    protected $pages;


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
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableFrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableTo;

    /**
     * @param string $name
     * @param \SRS\Model\Acl\Role $parent
     * @param bool $standAlone
     */
    public function __construct($name) {
        $this->name = $name;
//        $this->parent = $parent;
//        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
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

//    public function setChildren($children)
//    {
//        $this->children = $children;
//    }
//
//    public function getChildren()
//    {
//        return $this->children;
//    }
//
//    public function setParent($parent)
//    {
//        $this->parent = $parent;
//    }
//
//    public function getParent()
//    {
//        return $this->parent;
//    }


    public function setRegisterableFrom($registerableFrom)
    {
        if (is_string($registerableFrom)) {
            $registerableFrom = new \DateTime($registerableFrom);
        }
        $this->registerableFrom = $registerableFrom;
    }

    public function getRegisterableFrom()
    {
        return $this->registerableFrom;
    }

    public function setRegisterableTo($registerableTo)
    {
        if (is_string($registerableTo)) {
            $registerableTo = new \DateTime($registerableTo);
        }
        $this->registerableTo = $registerableTo;
    }

    public function getRegisterableTo()
    {
        return $this->registerableTo;
    }
}



class RoleRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\Acl\Role';

    public function findRegisterableNow()
    {
        $today = new \DateTime('now');
        $today = $today->format('Y-m-d');

//        $query = $this->_em->createQuery("SELECT r FROM {$this->entity} r WHERE r.registerable=true
//                AND ((r.registerableFrom >= {$today} OR r.registerableFrom IS NULL) AND (r.registerableTo <= {$today} OR r.registerableTo IS NULL))
//                ");
        $query = $this->_em->createQuery("SELECT r FROM {$this->entity} r WHERE r.registerable=true
              AND (r.registerableFrom <= '{$today}' OR r.registerableFrom IS NULL)
              AND (r.registerableTo >= '{$today}' OR r.registerableTo IS NULL)");
        return $query->getResult();
    }

    public function findApprovedUsersInRole($roleName) {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("approved", 1));
        $role = $this->_em->getRepository($this->_entityName)->findByName($roleName);
        if ($role == null) throw new RoleException('Role s tímto jménem neexistuje');
        $role = $role[0];
        return $role->users->matching($criteria);
    }
}

class RoleException extends \Exception
{

}
