<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class PatrolByIdQuery
{
    public function __construct(private int $patrolId)
    {
    }

    public function getPatrolId(): int
    {
        return $this->patrolId;
    }
}
