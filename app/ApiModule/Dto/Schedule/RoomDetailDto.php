<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o místnosti do FullCalendar.
 */
class RoomDetailDto
{
    use Nette\SmartObject;

    #[JMS\Type(values: 'int')]
    private int $id;

    #[JMS\Type(values: 'string')]
    private string $name;

    #[JMS\Type(values: 'int')]
    private int|null $capacity = null;

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

    public function getCapacity(): int|null
    {
        return $this->capacity;
    }

    public function setCapacity(int|null $capacity): void
    {
        $this->capacity = $capacity;
    }
}
