<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o programu do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramDetailDto
{
    use Nette\SmartObject;

    /** @JMS\Type("int") */
    private int $id;

    /** @JMS\Type("string") */
    private string $start;

    /** @JMS\Type("string") */
    private string $end;

    /** @JMS\Type("int") */
    private int $blockId;

    /** @JMS\Type("int") */
    private int $roomId;

    /** @JMS\Type("int") */
    private int $attendeesCount;

    /** @JMS\Type("boolean") */
    private bool $userAttends;

    /**
     * @JMS\Type("array")
     * @var int[]
     */
    private array $blocks;

    /** @JMS\Type("boolean") */
    private bool $blocked;

    /** @JMS\Type("boolean") */
    private bool $paid;

    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    public function getStart() : string
    {
        return $this->start;
    }

    public function setStart(string $start) : void
    {
        $this->start = $start;
    }

    public function getEnd() : string
    {
        return $this->end;
    }

    public function setEnd(string $end) : void
    {
        $this->end = $end;
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

    public function getAttendeesCount() : int
    {
        return $this->attendeesCount;
    }

    public function setAttendeesCount(int $attendeesCount) : void
    {
        $this->attendeesCount = $attendeesCount;
    }

    public function isUserAttends() : bool
    {
        return $this->userAttends;
    }

    public function setUserAttends(bool $userAttends) : void
    {
        $this->userAttends = $userAttends;
    }

    /**
     * @return int[]
     */
    public function getBlocks() : array
    {
        return $this->blocks;
    }

    /**
     * @param int[] $blocks
     */
    public function setBlocks(array $blocks) : void
    {
        $this->blocks = $blocks;
    }

    public function isBlocked() : bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked) : void
    {
        $this->blocked = $blocked;
    }

    public function isPaid() : bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid) : void
    {
        $this->paid = $paid;
    }
}
