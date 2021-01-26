<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\User\User;

class UserUpdatedEvent
{
    private User $user;

    private bool $approvedOld;

    public function __construct(User $user, bool $approvedOld)
    {
        $this->user        = $user;
        $this->approvedOld = $approvedOld;
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
