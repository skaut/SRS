<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita přihlášky na program.
 *
 * @ORM\Entity(repositoryClass="\App\Model\Program\Repositories\ProgramApplicationRepository")
 * @ORM\Table(name="program_application")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramApplication
{
    use Id;

    /**
     * Uživatel.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="programApplications", cascade={"persist"})
     */
    protected User $user;

    /**
     * Zapsaný program.
     *
     * @ORM\ManyToOne(targetEntity="Program", inversedBy="programApplications", cascade={"persist"})
     */
    protected Program $program;

    /**
     * Náhradník.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $alternate = false;

    /**
     * Čas přihlášení na program.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    protected DateTimeImmutable $createdAt;

    public function __construct(User $user, Program $program, bool $alternate = false)
    {
        $this->user      = $user;
        $this->program   = $program;
        $this->alternate = $alternate;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function setUser(User $user) : void
    {
        $this->user = $user;
    }

    public function getProgram() : Program
    {
        return $this->program;
    }

    public function setProgram(Program $program) : void
    {
        $this->program = $program;
    }

    public function isAlternate() : bool
    {
        return $this->alternate;
    }

    public function setAlternate(bool $alternate) : void
    {
        $this->alternate = $alternate;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt) : void
    {
        $this->createdAt = $createdAt;
    }
}
