<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\Program\Program;
use App\Model\User\User;

class UserRegisteredProgramAtQuery
{
    public function __construct(private readonly User $user, private readonly Program $program)
    {
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
