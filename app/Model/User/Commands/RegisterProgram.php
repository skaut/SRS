<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\Program\Program;
use App\Model\User\User;

class RegisterProgram
{
    public function __construct(
        private readonly User $user,
        private readonly Program $program,
        private readonly bool $notifyUser = true,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function isNotifyUser(): bool
    {
        return $this->notifyUser;
    }
}
