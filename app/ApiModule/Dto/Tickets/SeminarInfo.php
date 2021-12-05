<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o semináři pro propojení s mobilní aplikací.
 */
class SeminarInfo
{
    use Nette\SmartObject;

    /** @JMS\Type("string") */
    private string $name;

    /**
     * @JMS\Type("array<App\ApiModule\Dto\Tickets\SubeventInfo>")
     * @var SubeventInfo[]
     */
    private array $subevents;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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
}
