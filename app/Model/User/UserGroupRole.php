<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Acl\Role;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita uživatel-skupina-role.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_group_role')]
class UserGroupRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Uživatel.
     */
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    protected User $user;

    /**
     * Oddíl.
     */
    #[ORM\ManyToOne(targetEntity: Troop::class, cascade: ['persist'], inversedBy: 'users')]
    protected ?Troop $troop;

    /**
     * Družina.
     */
    #[ORM\ManyToOne(targetEntity: Patrol::class, cascade: ['persist'], inversedBy: 'users')]
    protected ?Patrol $patrol;

    /**
     * Role.
     */
    #[ORM\ManyToOne(targetEntity: Role::class, cascade: ['persist'])]
    protected Role $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getTroop(): ?Troop
    {
        return $this->troop;
    }

    public function setTroop(?Troop $troop): void
    {
        $this->troop = $troop;
    }

    public function getPatrol(): ?Patrol
    {
        return $this->patrol;
    }

    public function setPatrol(?Patrol $patrol): void
    {
        $this->patrol = $patrol;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }
}
