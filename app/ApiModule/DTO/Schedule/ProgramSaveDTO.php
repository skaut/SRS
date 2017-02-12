<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

class ProgramSaveDTO extends Nette\Object
{
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
     * @JMS\Type("DateTime")
     * @var \DateTime
     */
    private $start;

    /**
     * @JMS\Type("DateTime")
     * @var \DateTime
     */
    private $end;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getBlockId()
    {
        return $this->blockId;
    }

    /**
     * @param int $blockId
     */
    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;
    }

    /**
     * @return int
     */
    public function getRoomId()
    {
        return $this->roomId;
    }

    /**
     * @param int $roomId
     */
    public function setRoomId($roomId)
    {
        $this->roomId = $roomId;
    }

    /**
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param \DateTime $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param \DateTime $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }


}