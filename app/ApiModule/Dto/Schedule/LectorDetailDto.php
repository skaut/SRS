<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o lektorovi do FullCalendar.
 */
class LectorDetailDto
{
    use Nette\SmartObject;

    #[JMS\Type(values: 'int')]
    private int $id;

    #[JMS\Type(values: 'string')]
    private string $name;

    #[JMS\Type(values: 'string')]
    private string|null $about = null;

    #[JMS\Type(values: 'string')]
    private string|null $photo = null;

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

    public function getAbout(): string|null
    {
        return $this->about;
    }

    public function setAbout(string|null $about): void
    {
        $this->about = $about;
    }

    public function getPhoto(): string|null
    {
        return $this->photo;
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    public function setPhoto(string|null $photo): void
    {
        $this->photo = $photo;
    }
}
