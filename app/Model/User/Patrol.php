<?php

declare(strict_types=1);

namespace App\Model\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use function in_array;

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

    /**
     * Stav přihlášky - nepotvrzená přihláška slouží pro předávání údajů mezi kroky formuláře.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $confirmed = false;

    public function __construct(Troop $troop, string $name)
    {
        $this->usersRoles = new ArrayCollection();

        $this->troop = $troop;
        $this->name  = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTroop(): Troop
    {
        return $this->troop;
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function getUsersRoles(): Collection
    {
        return $this->usersRoles;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    /**
     * @param string[] $roleNames
     */
    public function countUsersInRoles(array $roleNames): int
    {
        $counter = 0;
        foreach ($this->usersRoles as $userRole) {
            if (in_array($userRole->getRole()->getSystemName(), $roleNames)) {
                $counter++;
            }
        }

        return $counter;
    }
}
