<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

class ProgramDetailDTO extends Nette\Object
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
     * @JMS\Type("string")
     * @var string
     */
    private $name;

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
     * @JMS\Type("int")
     * @var int
     */
    private $duration;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $attendeesCount;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $userAttends;

    /**
     * @JMS\Type("array<int>")
     * @var array
     */
    private $blocksPrograms;

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return int
     */
    public function getAttendeesCount()
    {
        return $this->attendeesCount;
    }

    /**
     * @param int $attendeesCount
     */
    public function setAttendeesCount($attendeesCount)
    {
        $this->attendeesCount = $attendeesCount;
    }

    /**
     * @return bool
     */
    public function isUserAttends()
    {
        return $this->userAttends;
    }

    /**
     * @param bool $userAttends
     */
    public function setUserAttends($userAttends)
    {
        $this->userAttends = $userAttends;
    }

    /**
     * @return array
     */
    public function getBlocksPrograms()
    {
        return $this->blocksPrograms;
    }

    /**
     * @param array $blocksPrograms
     */
    public function setBlocksPrograms($blocksPrograms)
    {
        $this->blocksPrograms = $blocksPrograms;
    }
}