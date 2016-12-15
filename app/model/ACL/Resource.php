<?php

namespace App\Model\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource")
 */
class Resource
{
    const ADMIN = 'admin';
    const CMS = 'cms';
    const ACL = 'acl';
    const PROGRAM = 'program';
    const CONFIGURATION = 'configuration';
    const EVIDENCE = 'evidence';
    const MAILING = 'mailing';

    public static $resources = [
        self::ADMIN,
        self::CMS,
        self::ACL,
        self::PROGRAM,
        self::CONFIGURATION,
        self::EVIDENCE,
        self::MAILING
    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string", unique=true) */
    protected $name;

    /** @ORM\OneToMany(targetEntity="\App\Model\ACL\Permission", mappedBy="resource", cascade={"persist"}) */
    protected $permissions;

    /**
     * Resource constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return array
     */
    public static function getResources()
    {
        return self::$resources;
    }

    /**
     * @param array $resources
     */
    public static function setResources($resources)
    {
        self::$resources = $resources;
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
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param mixed $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }
}