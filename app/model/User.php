<?php

namespace SRS\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property-read string $username
 * @property string $email
 * @property \Doctrine\Common\Collections\ArrayCollection $roles
 * @property string $firstName
 * @property string $lastName
 * @property string $nickName
 * @property string $sex
 * @property string $birthdate
 * @property int $skautISUserId
 * @property int $skautISPersonId
 */
class User extends \Nette\Object
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
    protected $username;
    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $email;

    /**
     * @ORM\ManyToMany(targetEntity="\SRS\Model\Role", inversedBy="users")
     * @var string
     */
    protected $roles;

     /**
     * @ORM\Column
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $nickName;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $sex;

    /**
     * @ORM\Column(type="date")
     * @var string
     */
    protected $birthdate;


   /**
    * @var int
    * @ORM\Column(type="integer", unique=true)
   */
    protected $skautISUserId;

    /**
     * @var int
     * @ORM\Column(type="integer", unique=true)
     */
    protected $skautISPersonId;


    /**
     * @param string
     * @return User
     */
    public function __construct($username)
    {
        $this->username = static::normalizeString($username);
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $birhdate
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return string
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    /**
     * @return string
     */
    public function getNickName()
    {
        return $this->nickName;
    }


    /**
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = static::normalizeString($email);
        return $this;
    }

    /**
     * @return string
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param mixed
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param $skautISPersonId
     */
    public function setSkautISPersonId($skautISPersonId)
    {
        $this->skautISPersonId = $skautISPersonId;
    }

    /**
     * @return int
     */
    public function getSkautISPersonId()
    {
        return $this->skautISPersonId;
    }

    /**
     * @param $skautISUserId
     */
    public function setSkautISUserId($skautISUserId)
    {
        $this->skautISUserId = $skautISUserId;
    }

    /**
     * @return int
     */
    public function getSkautISUserId()
    {
        return $this->skautISUserId;
    }

    /**
     * @param string
     * @return string
     */
    protected static function normalizeString($s)
    {
        $s = trim($s);
        return $s === "" ? NULL : $s;
    }
}