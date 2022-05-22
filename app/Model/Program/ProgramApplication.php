<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita přihlášky na program.
 */
#[ORM\Entity]
#[ORM\Table(name: 'program_application')]
class ProgramApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Uživatel.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'programApplications', cascade: ['persist'])]
    protected User $user;

    /**
     * Zapsaný program.
     */
    #[ORM\ManyToOne(targetEntity: Program::class, inversedBy: 'programApplications', cascade: ['persist'])]
    protected Program $program;

    /**
     * Náhradník.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $alternate = false;

    /**
     * Čas přihlášení na program.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $createdAt;

    public function __construct(User $user, Program $program)
    {
        $this->user      = $user;
        $this->program   = $program;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function isAlternate(): bool
    {
        return $this->alternate;
    }

    public function setAlternate(bool $alternate): void
    {
        $this->alternate = $alternate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
