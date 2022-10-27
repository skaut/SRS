<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

class ConfirmPatrol
{
    public function __construct(
        private int $patrolId
    ) {
    }

    public function getPatrolId(): int
    {
        return $this->patrolId;
    }
}
