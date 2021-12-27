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

    /**
     * @JMS\Type("array")
     * @var DateTimeImmutable[]
     */
    private array $subeventChecks;

    public function getAttendeeName(): string
    {
        return $this->attendeeName;
    }

    public function setAttendeeName(string $attendeeName): void
    {
        $this->attendeeName = $attendeeName;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return SubeventInfo[]
     */
    public function getSubevents(): array
    {
        return $this->subevents;
    }

    /**
     * @param SubeventInfo[] $subevents
     */
    public function setSubevents(array $subevents): void
    {
        $this->subevents = $subevents;
    }

    /**
     * @return DateTimeImmutable[]
     */
    public function getSubeventChecks(): array
    {
        return $this->subeventChecks;
    }

    /**
     * @param DateTimeImmutable[] $subeventChecks
     */
    public function setSubeventChecks(array $subeventChecks): void
    {
        $this->subeventChecks = $subeventChecks;
    }
}
