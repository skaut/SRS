<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\User\User;

class UserProgramsQuery
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
