<?php

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;


/**
 * Objekt pro přenos údajů o programu do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramDetailDTO
{
    use Nette\SmartObject;
    
    /**
     * @JMS\Type("int")
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $title;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $start;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $end;

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
     * @JMS\Type("array")
     * @var int[]
     */
    private $blocks;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $blocked;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $paid;


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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param string $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param string $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
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
     * @return \int[]
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param \int[] $blocks
     */
    public function setBlocks($blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->blocked;
    }

    /**
     * @param bool $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
    }

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->paid;
    }

    /**
     * @param bool $paid
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }
}
