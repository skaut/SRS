<?php

namespace App\Model\ACL;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Permission
{
    const MANAGE = 'manage';
    const ACCESS = 'access';
    const MANAGE_OWN_PROGRAMS = 'manage_own_programs';
    const MANAGE_ALL_PROGRAMS = 'manage_all_programs';
    const MANAGE_HARMONOGRAM = 'manage_harmonogram';
    const MANAGE_ROOMS = 'manage_rooms';
    const MANAGE_CATEGORIES = 'manage_categories';
    const CHOOSE_PROGRAMS = 'choose_programs';

//    public static $permissions = [
//        self::MANAGE,
//        self::ACCESS,
//        self::MANAGE_OWN_PROGRAMS,
//        self::MANAGE_ALL_PROGRAMS,
//        self::MANAGE_HARMONOGRAM,
//        self::CHOOSE_PROGRAMS
//    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string") */
    protected $name;

    /** @ORM\ManyToMany(targetEntity="\SRS\model\Acl\Role", mappedBy="permissions", cascade={"persist"}) */
    protected $roles;

    /** @ORM\ManyToOne(targetEntity="\SRS\Model\Acl\Resource", inversedBy="permissions", cascade={"persist"}) */
    protected $resource;

    /**
     * Permission constructor.
     * @param $name
     * @param $resource
     */
    public function __construct($name, $resource)
    {
        $this->name = $name;
        $this->resource = $resource;
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
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param mixed $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }
}