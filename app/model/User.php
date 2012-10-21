<?php

namespace SRS\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property-read int $id
 * @property-read string $username
 * @property string $email
 * @property string $password
 * @property string $role
 */
class User extends \Nette\Object
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;
    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    private $username;
    /**
     * @ORM\Column
     * @var string
     */
    private $email;
    /**
     * @ORM\Column
     * @var string
     */
    private $password;
    /**
     * @ORM\Column
     * @var string
     */
    private $role;

    /**
     * @param string
     * @return User
     */
    public function __construct($username)
    {
        $this->username = static::normalizeString($username);
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = static::normalizeString($password);
        return $this;
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
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string
     * @return User
     */
    public function setRole($role)
    {
        $this->role = static::normalizeString($role);
        return $this;
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