<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Tickets;

use DateTime;
use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů pro propojení s mobilní aplikací.
 */
class ConnectionDto
{
    use Nette\SmartObject;

    /** @JMS\Type("string") */
    private string $seminarName;

    /**
     * @return string
     */
    public function getSeminarName(): string
    {
        return $this->seminarName;
    }

    /**
     * @param string $seminarName
     */
    public function setSeminarName(string $seminarName): void
    {
        $this->seminarName = $seminarName;
    }
}
