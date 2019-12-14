<?php

declare(strict_types=1);

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

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
     * @var string
     */
    public const MANAGE = 'manage';

    /**
     * Oprávnění přistupovat.
     * @var string
     */
    public const ACCESS = 'access';

    /**
     * Oprávnění spravovat programy, u kterých je uživatel lektor.
     * @var string
     */
    public const MANAGE_OWN_PROGRAMS = 'manage_own_programs';

    /**
     * Oprávnění spravovat všechny programy.
     * @var string
     */
    public const MANAGE_ALL_PROGRAMS = 'manage_all_programs';

    /**
     * Oprávnění spravovat harmonogram.
     * @var string
     */
    public const MANAGE_SCHEDULE = 'manage_schedule';

    /**
     * Oprávnění spravovat místnosti.
     * @var string
     */
    public const MANAGE_ROOMS = 'manage_rooms';

    /**
     * Oprávnění spravovat kategorie bloků.
     * @var string
     */
    public const MANAGE_CATEGORIES = 'manage_categories';

    /**
     * Oprávnění přihlašovat se na programy.
     * @var string
     */
    public const CHOOSE_PROGRAMS = 'choose_programs';

    /** @var string[] */
    public static $permissions = [
        self::MANAGE,
        self::ACCESS,
        self::MANAGE_OWN_PROGRAMS,
        self::MANAGE_ALL_PROGRAMS,
        self::MANAGE_SCHEDULE,
        self::CHOOSE_PROGRAMS,
    ];

    use Id;

    /**
     * Název oprávnění.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Role s tímto oprávněním.
     * @ORM\ManyToMany(targetEntity="\App\Model\ACL\Role", mappedBy="permissions", cascade={"persist"})
     * @var Collection<Role>|Role[]
     */
    protected $roles;

    /**
     * Prostředek oprávnění.
     * @ORM\ManyToOne(targetEntity="\App\Model\ACL\Resource", inversedBy="permissions", cascade={"persist"})
     * @var Resource
     */
    protected $resource;


    public function __construct(string $name, Resource $resource)
    {
        $this->name     = $name;
        $this->resource = $resource;
        $this->roles    = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return Collection<Role>|Role[]
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    public function getResource() : Resource
    {
        return $this->resource;
    }
}
