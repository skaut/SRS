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

    #[JMS\Type(values: 'string')]
    private string $name;

    /** @var SubeventInfo[] */
    #[JMS\Type(values: 'array<App\ApiModule\Dto\Tickets\SubeventInfo>')]
    private array $subevents;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param SubeventInfo[] $subevents
     */
    public function setSubevents(array $subevents): void
    {
        $this->subevents = $subevents;
    }
}
