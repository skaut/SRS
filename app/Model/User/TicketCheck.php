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
    private ?int $id = null;

    /**
     * Datum a čas kontroly.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $datetime;

    /**
     * @param User     $user     Uživatel
     * @param Subevent $subevent Podakce
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
        protected User $user,
        #[ORM\ManyToOne(targetEntity: Subevent::class, cascade: ['persist'])]
        protected Subevent $subevent
    ) {
        $this->datetime = new DateTimeImmutable();
    }

    public function getId(): ?int
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
