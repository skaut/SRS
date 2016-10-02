<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\Acl;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;


/**
 * Entita uzivatelske role
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\Acl\RoleRepository")
 *
 * @property-read int $id
 * @property string $name
 * @property bool $system
 * @property bool $registerable
 * @property bool $approvedAfterRegistration
 * @property integer $usersLimit
 * @property bool $pays
 * @property integer $fee
 * @property string $feeWord
 * @property bool $displayInList
 * @property bool $displayCapacity
 * @property bool $displayArrivalDeparture
 * @property bool $syncedWithSkautIS
 * @property \Doctrine\Common\Collections\ArrayCollection $incompatibleWithRole
 * @property \Doctrine\Common\Collections\ArrayCollection $incompatibleRoles
 * @property \Doctrine\Common\Collections\ArrayCollection $registerableCategories
 * @property \DateTime|string $registerableFrom
 * @property \DateTime|string $registerableTo
 * @property \Doctrine\Common\Collections\ArrayCollection $users
 * @property \Doctrine\Common\Collections\ArrayCollection $permissions
 */
class Role extends \SRS\Model\BaseEntity
{
    const GUEST = 'guest';
    const REGISTERED = 'Nepřihlášený';
    const ATTENDEE = 'Účastník';
    const SERVICE_TEAM = 'Servis Tým';
    const LECTOR = 'Lektor';
    const ORGANIZER = 'Organizátor';
    const ADMIN = 'Administrátor';

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $name;


    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\User", mappedBy="roles", cascade={"persist"})
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
     * Je role po registraci rovnou schvalena?
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $approvedAfterRegistration = false;


    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableFrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $registerableTo;

    /**
     * Maximální počet osob v roli
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $usersLimit;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $pays = false;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $fee;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $feeWord;


    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $displayInList;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $displayCapacity;

//    /**
//     * @var boolean
//     * @ORM\Column(type="boolean", nullable=true)
//     */
//    protected $displayArrivalDeparture;


    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $syncedWithSkautIS = true;


    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="incompatibleRoles", cascade={"persist"})
     * @var mixed
     */
    protected $incompatibleWithRole;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", inversedBy="incompatibleWithRole", cascade={"persist"})
     * @ORM\JoinTable(name="role_role",
     *      joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_role_id", referencedColumnName="id")}
     *      )
     * @var mixed
     */
    protected $incompatibleRoles;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="registerableRoles", cascade={"persist"})
     * @var mixed
     */
    protected $registerableCategories;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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

    public function setPermissions($permissions)
    {
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

    public function getApprovedAfterRegistration()
    {
        return $this->approvedAfterRegistration;
    }

    public function setApprovedAfterRegistration($approvedAfterRegistration)
    {
        $this->approvedAfterRegistration = $approvedAfterRegistration;
    }


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

    public function setUsersLimit($usersLimit)
    {
        $this->usersLimit = $usersLimit;
    }

    public function getUsersLimit()
    {
        return $this->usersLimit;
    }

    /**
     * @param int $fee
     */
    public function setFee($fee)
    {
        $this->fee = (int) $fee;
    }

    /**
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param string $fee_word
     */
    public function setFeeWord($fee_word)
    {
        $this->feeWord = $fee_word;
    }

    /**
     * @return string
     */
    public function getFeeWord()
    {
        return $this->feeWord;
    }

    /**
     * @param mixed $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param boolean $pays
     */
    public function setPays($pays)
    {
        $this->pays = $pays;
    }

    /**
     * @return boolean
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * @param boolean $syncedWithSkautIS
     */
    public function setSyncedWithSkautIS($syncedWithSkautIS)
    {
        $this->syncedWithSkautIS = $syncedWithSkautIS;
    }

    /**
     * @return boolean
     */
    public function getSyncedWithSkautIS()
    {
        return $this->syncedWithSkautIS;
    }

    /**
     * @return boolean $displayInList
     */
    public function isDisplayInList()
    {
        return $this->displayInList;
    }

    /**
     * @param boolean $displayInList
     */
    public function setDisplayInList($displayInList)
    {
        $this->displayInList = $displayInList;
    }

//    /**
//     * @return boolean
//     */
//    public function isDisplayArrivalDeparture()
//    {
//        return $this->displayArrivalDeparture;
//    }
//
//    /**
//     * @param boolean $displayArrivalDeparture
//     */
//    public function setDisplayArrivalDeparture($displayArrivalDeparture)
//    {
//        $this->displayArrivalDeparture = $displayArrivalDeparture;
//    }


}

/**
 * Doctrine Repozitar pro entitu Role.
 *
 * Pridava dalsi metody pro vyhledavni roli v databazi
 */
class RoleRepository extends \Doctrine\ORM\EntityRepository
{
    public function findRegisterableNow()
    {
        $today = new \DateTime('now');
        $today = $today->format('Y-m-d');

        $query = $this->_em->createQuery("SELECT r FROM {$this->_entityName} r WHERE r.registerable=true
              AND (r.registerableFrom <= '{$today}' OR r.registerableFrom IS NULL)
              AND (r.registerableTo >= '{$today}' OR r.registerableTo IS NULL)");
        return $query->getResult();
    }

    public function findApprovedUsersInRole($roleName)
    {
        $query = $this->_em->createQuery("SELECT u FROM \SRS\model\User u JOIN u.roles r WHERE u.approved=true AND r.name='$roleName'");
        return $query->getResult();
    }
}

class RoleException extends \Exception
{

}
