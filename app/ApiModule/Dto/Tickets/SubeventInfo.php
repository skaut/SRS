<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o podakci.
 */
class SubeventInfo
{
    use Nette\SmartObject;

    public function __construct(
        /** @JMS\Type("int") */
        private int $id,
        /** @JMS\Type("string") */
        private string $name
    ) {
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
