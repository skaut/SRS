<?php

declare(strict_types=1);

namespace App\Model\Application\Events;

use App\Model\User\User;

class ApplicationUpdatedEvent
{
    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
