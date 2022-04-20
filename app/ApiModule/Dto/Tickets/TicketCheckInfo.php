<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use DateTimeImmutable;
use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o kontrole vstupenky
 */
class TicketCheckInfo
{
    use Nette\SmartObject;

    /** @JMS\Type("string") */
    private string $attendeeName;

    /**
     * @JMS\Type("array")
     * @var string[]
     */
    private array $roles;

    /**
     * @JMS\Type("array<App\ApiModule\Dto\Tickets\SubeventInfo>")
     * @var SubeventInfo[]
     */
    private array $subevents;

    /** @JMS\Type("boolean") */
    private bool $hasSubevent;

    /**
     * @JMS\Type("array")
     * @var DateTimeImmutable[]
     */
    private array $subeventChecks;

    public function setAttendeeName(string $attendeeName): void
    {
        $this->attendeeName = $attendeeName;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @param SubeventInfo[] $subevents
     */
    public function setSubevents(array $subevents): void
    {
        $this->subevents = $subevents;
    }

    public function setHasSubevent(bool $hasSubevent): void
    {
        $this->hasSubevent = $hasSubevent;
    }

    /**
     * @param DateTimeImmutable[] $subeventChecks
     */
    public function setSubeventChecks(array $subeventChecks): void
    {
        $this->subeventChecks = $subeventChecks;
    }
}
