<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use DateTimeImmutable;
use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o kontrole vstupenky.
 */
class TicketCheckInfo
{
    use Nette\SmartObject;

    /** Jméno účastníka. */
    #[JMS\Type(values: 'string')]
    private string $attendeeName;

    /** Věk účastníka. */
    #[JMS\Type(values: 'integer')]
    private int $attendeeAge;

    /** Odkaz na fotku účastníka. */
    #[JMS\Type(values: 'string')]
    private string|null $attendeePhoto;

    /** Má účastník propojený účet? */
    #[JMS\Type(values: 'boolean')]
    private bool $attendeeMember;

    /**
     * Role účastníka.
     * @var string[]
     */
    #[JMS\Type(values: 'array')]
    private array $roles;

    /**
     * Podakce účastníka.
     * @var SubeventInfo[]
     */
    #[JMS\Type(values: 'array<App\ApiModule\Dto\Tickets\SubeventInfo>')]
    private array $subevents;

    /** Má účastník podakci? */
    #[JMS\Type(values: 'boolean')]
    private bool $hasSubevent;

    /**
     * Seznam časů kontroly vstupenky.
     * @var DateTimeImmutable[]
     */
    #[JMS\Type(values: 'array<DateTimeImmutable>')]
    private array $subeventChecks;

    public function setAttendeeName(string $attendeeName): void
    {
        $this->attendeeName = $attendeeName;
    }

    public function setAttendeeAge(int $attendeeAge): void
    {
        $this->attendeeAge = $attendeeAge;
    }

    public function setAttendeePhoto(string|null $attendeePhoto): void
    {
        $this->attendeePhoto = $attendeePhoto;
    }

    public function setAttendeeMember(bool $attendeeMember): void
    {
        $this->attendeeMember = $attendeeMember;
    }

    /** @param string[] $roles */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /** @param SubeventInfo[] $subevents */
    public function setSubevents(array $subevents): void
    {
        $this->subevents = $subevents;
    }

    public function setHasSubevent(bool $hasSubevent): void
    {
        $this->hasSubevent = $hasSubevent;
    }

    /** @param DateTimeImmutable[] $subeventChecks */
    public function setSubeventChecks(array $subeventChecks): void
    {
        $this->subeventChecks = $subeventChecks;
    }
}
