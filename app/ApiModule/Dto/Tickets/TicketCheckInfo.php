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

    #[JMS\Type(values: 'string')]
    private string $attendeeName;

    #[JMS\Type(values: 'integer')]
    private int $attendeeAge;

    #[JMS\Type(values: 'string')]
    private string $attendeePhoto;

    /** @var string[] */
    #[JMS\Type(values: 'array')]
    private array $roles;

    /** @var SubeventInfo[] */
    #[JMS\Type(values: 'array<App\ApiModule\Dto\Tickets\SubeventInfo>')]
    private array $subevents;

    #[JMS\Type(values: 'boolean')]
    private bool $hasSubevent;

    /** @var DateTimeImmutable[] */
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

    public function setAttendeePhoto(string $attendeePhoto): void
    {
        $this->attendeePhoto = $attendeePhoto;
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
