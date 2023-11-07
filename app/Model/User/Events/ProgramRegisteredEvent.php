<?php

declare(strict_types=1);

namespace App\Model\User\Events;

use App\Model\Program\Program;
use App\Model\User\User;

class ProgramRegisteredEvent
{
    public function __construct(
        private readonly User $user,
        private readonly Program $program,
        private readonly bool $alternate,
        private readonly bool $notifyUser,
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
