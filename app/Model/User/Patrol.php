<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita družina.
 */
#[ORM\Entity]
#[ORM\Table(name: 'patrol')]
class Patrol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Název družiny.
     */
    #[ORM\Column(type: 'string')]
    protected string $name;

    /**
     * Oddíl družiny.
     */
    #[ORM\ManyToOne(targetEntity: Troop::class, inversedBy: 'patrols', cascade: ['persist'])]
    protected Troop $troop;

    /**
     * Uživatelé.
     *
     * @var Collection<int, UserGroupRole>
     */
    #[ORM\OneToMany(mappedBy: 'patrol', targetEntity: UserGroupRole::class, cascade: ['persist'])]
    protected Collection $usersRoles;

    public function __construct()
    {
        $this->usersRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTroop(): Troop
    {
        return $this->troop;
    }

    public function setTroop(Troop $troop): void
    {
        $this->troop = $troop;
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function getUsersRoles(): Collection
    {
        return $this->usersRoles;
    }
}
