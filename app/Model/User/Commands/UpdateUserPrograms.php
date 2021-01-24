<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\User;

class UpdateUserPrograms
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
