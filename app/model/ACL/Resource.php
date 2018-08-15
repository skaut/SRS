<?php

declare(strict_types=1);

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Entita prostředek.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="ResourceRepository")
 * @ORM\Table(name="resource")
 */
class Resource
{
    /**
     * Administrace.
     */
    public const ADMIN = 'admin';

    /**
     * Web.
     */
    public const CMS = 'cms';

    /**
     * Role.
     */
    public const ACL = 'acl';

    /**
     * Program.
     */
    public const PROGRAM = 'program';

    /**
     * Nastavení.
     */
    public const CONFIGURATION = 'configuration';

    /**
     * Uživatelé.
     */
    public const USERS = 'users';

    /**
     * Mailing.
     */
    public const MAILING = 'mailing';

    public static $resources = [
        self::ADMIN,
        self::CMS,
        self::ACL,
        self::PROGRAM,
        self::CONFIGURATION,
        self::USERS,
        self::MAILING,
    ];

    use Identifier;

    /**
     * Název prostředku.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Oprávnění s tímto prostředkem.
     * @ORM\OneToMany(targetEntity="\App\Model\ACL\Permission", mappedBy="resource", cascade={"persist"})
     * @var Collection
     */
    protected $permissions;


    public function __construct(string $name)
    {
        $this->name        = $name;
        $this->permissions = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @return Collection
     */
    public function getPermissions() : Collection
    {
        return $this->permissions;
    }
}
