<?php

declare(strict_types=1);

namespace App\ApiModule\Dto\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o programu do FullCalendar.
 */
class ProgramDetailDto
{
    use Nette\SmartObject;

    #[JMS\Type(values: 'int')]
    private int $id;

    #[JMS\Type(values: 'string')]
    private string|null $start = null;

    #[JMS\Type(values: 'string')]
    private string|null $end = null;

    #[JMS\Type(values: 'int')]
    private int|null $blockId = null;

    #[JMS\Type(values: 'int')]
    private int|null $roomId = null;

    #[JMS\Type(values: 'int')]
    private int|null $attendeesCount = null;

    #[JMS\Type(values: 'int')]
    private int|null $alternatesCount = null;

    #[JMS\Type(values: 'boolean')]
    private bool|null $userAttends = null;

    #[JMS\Type(values: 'boolean')]
    private bool|null $userAlternates = null;

    /** @var int[] */
    #[JMS\Type(values: 'array<int>')]
    private array|null $sameBlockPrograms = null;

    /** @var int[] */
    #[JMS\Type(values: 'array<int>')]
    private array|null $overlappingPrograms = null;

    #[JMS\Type(values: 'boolean')]
    private bool|null $blocked = null;

    #[JMS\Type(values: 'boolean')]
    private bool|null $paid = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStart(): string
    {
        return $this->start;
    }

    public function setStart(string $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): string
    {
        return $this->end;
    }

    public function setEnd(string $end): void
    {
        $this->end = $end;
    }

    public function getBlockId(): int
    {
        return $this->blockId;
    }

    public function setBlockId(int $blockId): void
    {
        $this->blockId = $blockId;
    }

    public function getRoomId(): int|null
    {
        return $this->roomId;
    }

    public function setRoomId(int|null $roomId): void
    {
        $this->roomId = $roomId;
    }

    public function getAttendeesCount(): int
    {
        return $this->attendeesCount;
    }

    public function setAttendeesCount(int $attendeesCount): void
    {
        $this->attendeesCount = $attendeesCount;
    }

    public function getAlternatesCount(): int|null
    {
        return $this->alternatesCount;
    }

    public function setAlternatesCount(int $alternatesCount): void
    {
        $this->alternatesCount = $alternatesCount;
    }

    public function getUserAttends(): bool|null
    {
        return $this->userAttends;
    }

    public function setUserAttends(bool|null $userAttends): void
    {
        $this->userAttends = $userAttends;
    }

    public function getUserAlternates(): bool|null
    {
        return $this->userAlternates;
    }

    public function setUserAlternates(bool|null $userAlternates): void
    {
        $this->userAlternates = $userAlternates;
    }

    /** @return int[]|null */
    public function getSameBlockPrograms(): array|null
    {
        return $this->sameBlockPrograms;
    }

    /** @param int[] $sameBlockPrograms */
    public function setSameBlockPrograms(array|null $sameBlockPrograms): void
    {
        $this->sameBlockPrograms = $sameBlockPrograms;
    }

    /** @return int[]|null */
    public function getOverlappingPrograms(): array|null
    {
        return $this->overlappingPrograms;
    }

    /** @param int[] $overlappingPrograms */
    public function setOverlappingPrograms(array|null $overlappingPrograms): void
    {
        $this->overlappingPrograms = $overlappingPrograms;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): void
    {
        $this->blocked = $blocked;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): void
    {
        $this->paid = $paid;
    }
}
