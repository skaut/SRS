<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\User\User;

class UserUpdatedEvent
{
    public function __construct(private readonly User $user, private readonly bool $approvedOld)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isApprovedOld(): bool
    {
        return $this->approvedOld;
    }
}
