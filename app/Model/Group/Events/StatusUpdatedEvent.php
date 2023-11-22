<?php

declare(strict_types=1);

namespace App\Model\Group\Events;

use App\Model\Acl\Role;
use App\Model\Group\Status;
use Doctrine\Common\Collections\Collection;

class StatusUpdatedEvent
{
    /** @param Collection<int, Role> $registerableRolesOld */
    public function __construct(private Status $status, private Collection $registerableRolesOld)
    {
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    /** @return Collection<int, Role> */
    public function getRegisterableRolesOld(): Collection
    {
        return $this->registerableRolesOld;
    }
}
