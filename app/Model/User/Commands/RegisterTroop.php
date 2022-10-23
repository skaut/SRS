<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\User;

class RegisterTroop
{
    public function __construct(private User $leader)
    {
    }

    public function getLeader(): User
    {
        return $this->leader;
    }
}
