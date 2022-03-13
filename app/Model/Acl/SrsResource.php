<?php

declare(strict_types=1);

namespace App\Model\Acl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita prostředek.
 *
 * @ORM\Entity
 * @ORM\Table(name="resource")
 */
class SrsResource
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

    /**
     * Platby.
     */
    public const PAYMENTS = 'payments';

    /** @var string[] */
    public static array $resources = [
        self::ADMIN,
        self::CMS,
        self::ACL,
        self::PROGRAM,
        self::CONFIGURATION,
        self::USERS,
        self::MAILING,
        self::PAYMENTS,
    ];

    /**
     * @ORM\Column(type="integer", nullable=FALSE)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private int $id;

    /**
     * Název prostředku.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Oprávnění s tímto prostředkem.
     *
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="resource", cascade={"persist"})
     *
     * @var Collection<int, Permission>
     */
    protected Collection $permissions;

    public function __construct(string $name)
    {
        $this->name        = $name;
        $this->permissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }
}
