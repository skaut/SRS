<?php

declare(strict_types=1);

namespace App\Model\Acl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita prostředek.
 *
 * @ORM\Entity
 * @ORM\Table(name="resource")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
    use Id;

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
     * @var Collection<Permission>
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
     * @return Collection<Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }
}
