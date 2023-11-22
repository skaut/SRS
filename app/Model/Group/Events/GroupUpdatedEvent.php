<?php

declare(strict_types=1);

namespace App\Model\Group\Events;

use App\Model\User\Group;

class GroupUpdatedEvent
{
    public function __construct(private Group $group, private bool $approvedOld)
    {
    }

    public function getGroup(): Group
    {
        return $this->user;
    }

    public function isApprovedOld(): bool
    {
        return $this->approvedOld;
    }
}
