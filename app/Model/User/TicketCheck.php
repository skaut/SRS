<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Structure\Subevent;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita kontrola vstupenky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'ticket_check')]
class TicketCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Uživatel.
     */
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    protected User $user;

    /**
     * Podakce.
     */
    #[ORM\ManyToOne(targetEntity: Subevent::class, cascade: ['persist'])]
    protected Subevent $subevent;

    /**
     * Datum a čas kontroly.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $datetime;

    public function __construct(User $user, Subevent $subevent)
    {
        $this->user     = $user;
        $this->subevent = $subevent;
        $this->datetime = new DateTimeImmutable();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubevent(): Subevent
    {
        return $this->subevent;
    }

    public function getDatetime(): DateTimeImmutable
    {
        return $this->datetime;
    }
}
