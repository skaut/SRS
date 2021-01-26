<?php

declare(strict_types=1);

namespace App\Model\Application\Events;

use App\Model\User\User;

class ApplicationUpdatedEvent
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
