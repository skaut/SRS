<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\Program\Program;
use App\Model\User\User;

class UnregisterProgram
{
    private User $user;

    private Program $program;

    private bool $notifyUser;

    public function __construct(User $user, Program $program, bool $notifyUser = true)
    {
        $this->user       = $user;
        $this->program    = $program;
        $this->notifyUser = $notifyUser;
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
