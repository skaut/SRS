<?php

declare(strict_types=1);

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o nově vytvořeném bloku z FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramSaveDTO
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
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s'>")
     * \DateTime
     */
    private $start;


    public function getId() : int
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

    public function getRoomId() : int
    {
        return $this->roomId;
    }

    public function setRoomId(int $roomId) : void
    {
        $this->roomId = $roomId;
    }

    public function getStart() : \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start) : void
    {
        $this->start = $start;
    }
}
