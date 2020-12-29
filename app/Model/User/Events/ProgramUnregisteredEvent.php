<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\Program\Program;
use App\Model\User\User;

class ProgramUnregisteredEvent
{
    private User $user;

    private Program $program;

    private bool $notifyUser;

    public function __construct(User $user, Program $program, bool $notifyUser = false)
    {
        $this->user       = $user;
        $this->program    = $program;
        $this->notifyUser = $notifyUser;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function getProgram() : Program
    {
        return $this->program;
    }

    public function isNotifyUser(): bool
    {
        return $this->notifyUser;
    }
}