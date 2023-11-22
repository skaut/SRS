<?php

declare(strict_types=1);

namespace App\Model\Group\Commands;

use App\Model\Group\Group;

class RemoveGroup
{
    public function __construct(private Group $group)
    {
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}
