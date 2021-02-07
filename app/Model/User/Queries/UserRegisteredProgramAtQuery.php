<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\Program\Program;
use App\Model\User\User;

class UserRegisteredProgramAtQuery
{
    private User $user;

    private Program $program;

    public function __construct(User $user, Program $program)
    {
        $this->user    = $user;
        $this->program = $program;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProgram(): Program
    {
        return $this->program;
    }
}
