<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\User;

class UpdateUserPrograms
{
    public function __construct(private User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
