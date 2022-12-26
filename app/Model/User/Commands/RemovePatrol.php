<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\Patrol;

class RemovePatrol
{
    public function __construct(private Patrol $patrol)
    {
    }

    public function getPatrol(): Patrol
    {
        return $this->patrol;
    }
}
