<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\Program\Program;
use App\Model\User\User;

class UserApplicationUpdatedEvent
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser() : User
    {
        return $this->user;
    }
}