<?php

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita oprávnění.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="PermissionRepository")
 * @ORM\Table(name="permission")
 */
class Permission
{
    /**
     * Oprávnění spravovat.
     */
    const MANAGE = 'manage';

    /**
     * Oprávnění přistupovat.
     */
    const ACCESS = 'access';

    /**
     * Oprávnění spravovat programy, u kterých je uživatel lektor.
     */
    const MANAGE_OWN_PROGRAMS = 'manage_own_programs';

    /**
     * Oprávnění spravovat všechny programy.
     */
    const MANAGE_ALL_PROGRAMS = 'manage_all_programs';

    /**
     * Oprávnění spravovat harmonogram.
     */
    const MANAGE_SCHEDULE = 'manage_schedule';

    /**
     * Oprávnění spravovat místnosti.
     */
    const MANAGE_ROOMS = 'manage_rooms';

    /**
     * Oprávnění spravovat kategorie bloků.
     */
    const MANAGE_CATEGORIES = 'manage_categories';

    /**
     * Oprávnění přihlašovat se na programy.
     */
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
     * Název oprávnění.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Role s tímto oprávněním.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", mappedBy="permissions", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $roles;

    /**
     * Prostředek oprávnění.
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
     * @return Resource
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