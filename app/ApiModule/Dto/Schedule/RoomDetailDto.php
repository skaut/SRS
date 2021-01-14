<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o místnosti do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoomDetailDto
{
    use Nette\SmartObject;

    /** @JMS\Type("int") */
    private int $id;

    /** @JMS\Type("string") */
    private string $name;

    /** @JMS\Type("integer") */
    private ?int $capacity = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity;
    }
}
