<?php

declare(strict_types=1);

namespace App\Model\Group\Commands;

use App\Model\Group\Status;

class RemoveStatus
{
    public function __construct(private Status $status)
    {
    }

    public function getStatus(): Status
    {
        return $this->status;
    }
}
