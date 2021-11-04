<?php

declare(strict_types=1);

namespace App\Model\User;

use DateTimeImmutable;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita kontrola vstupenky.
 *
 * @ORM\Entity
 * @ORM\Table(name="ticket_check")
 */
class TicketCheck
{
    use Id;

    /**
     * Uživatel.
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ticketChecks", cascade={"persist"})
     */
    protected User $user;

    /**
     * Datum a čas kontroly.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    protected DateTimeImmutable $datetime;

    public function __construct()
    {
        $this->datetime = new DateTimeImmutable();
    }

    public function getId(): int
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

    public function getDatetime(): DateTimeImmutable
    {
        return $this->datetime;
    }
}
