<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\Program\Program;
use App\Model\User\User;

class ProgramRegisteredEvent
{
    public function __construct(
        private User $user,
        private Program $program,
        private bool $alternate,
        private bool $notifyUser
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

    public function isAlternate(): bool
    {
        return $this->alternate;
    }

    public function isNotifyUser(): bool
    {
        return $this->notifyUser;
    }
}
