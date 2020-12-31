<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\Program\Program;
use App\Model\User\User;

class RegisterProgram
{
    private User $user;

    private Program $program;

    private bool $alternate;

    private bool $notifyUser;

    public function __construct(User $user, Program $program, bool $alternate = false, bool $notifyUser = false)
    {
        $this->user       = $user;
        $this->program    = $program;
        $this->alternate  = $alternate;
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

    public function isAlternate() : bool
    {
        return $this->alternate;
    }

    public function isNotifyUser() : bool
    {
        return $this->notifyUser;
    }
}
