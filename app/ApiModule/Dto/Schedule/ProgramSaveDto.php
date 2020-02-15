<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use DateTimeImmutable;
use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o nově vytvořeném bloku z FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramSaveDto
{
    use Nette\SmartObject;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $blockId;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $roomId;

    /**
     * @JMS\Type("DateTimeImmutable<'Y-m-d\TH:i:s'>")
     * @var DateTimeImmutable
     */
    private $start;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    public function getBlockId() : int
    {
        return $this->blockId;
    }

    public function setBlockId(int $blockId) : void
    {
        $this->blockId = $blockId;
    }

    public function getRoomId() : ?int
    {
        return $this->roomId;
    }

    public function setRoomId(?int $roomId) : void
    {
        $this->roomId = $roomId;
    }

    public function getStart() : DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(DateTimeImmutable $start) : void
    {
        $this->start = $start;
    }
}
