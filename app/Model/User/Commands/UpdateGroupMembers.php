<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

class UpdateGroupMembers
{
    /**
     * @param string[][] $persons
     */
    public function __construct(
        private string $type,
        private int $troopId,
        private ?int $patrolId,
        private array $persons,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTroopId(): int
    {
        return $this->troopId;
    }

    public function getPatrolId(): ?int
    {
        return $this->patrolId;
    }

    /**
     * @return string[][]
     */
    public function getPersons(): array
    {
        return $this->persons;
    }
}
