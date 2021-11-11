<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use DateTime;
use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o vstupence.
 */
class TicketDto
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
     * @JMS\Type("array")
     * @var string[]
     */
    private array $subevents;

    /**
     * @JMS\Type("array")
     * @var DateTime[]
     */
    private array $checks;

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
     * @return string[]
     */
    public function getSubevents(): array
    {
        return $this->subevents;
    }

    /**
     * @param string[] $subevents
     */
    public function setSubevents(array $subevents): void
    {
        $this->subevents = $subevents;
    }

    /**
     * @return DateTime[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    /**
     * @param DateTime[] $checks
     */
    public function setChecks(array $checks): void
    {
        $this->checks = $checks;
    }
}
