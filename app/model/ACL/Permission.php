<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity(repositoryClass="PermissionRepository")
 * @ORM\Table(name="permission")
 */
class Permission
{
    const MANAGE = 'manage';
    const ACCESS = 'access';
    const MANAGE_OWN_PROGRAMS = 'manage_own_programs';
    const MANAGE_ALL_PROGRAMS = 'manage_all_programs';
    const MANAGE_SCHEDULE = 'manage_schedule';
    const MANAGE_ROOMS = 'manage_rooms';
    const MANAGE_CATEGORIES = 'manage_categories';
    const CHOOSE_PROGRAMS = 'choose_programs';

    public static $permissions = [
        self::MANAGE,
        self::ACCESS,
        self::MANAGE_OWN_PROGRAMS,
        self::MANAGE_ALL_PROGRAMS,
        self::MANAGE_SCHEDULE,
        self::CHOOSE_PROGRAMS
    ];

    use Identifier;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", mappedBy="permissions", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\ACL\Resource", inversedBy="permissions", cascade={"persist"})
     * @var Resource
     */
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
        $this->roles = new ArrayCollection();
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
     * @return ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return ArrayCollection
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param ArrayCollection $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }
}