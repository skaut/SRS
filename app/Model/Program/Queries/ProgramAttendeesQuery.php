<?php

declare(strict_types=1);

namespace App\Model\Program\Queries;

use App\Model\Program\Program;

class ProgramAttendeesQuery
{
    public function __construct(private Program $program)
    {
    }

    public function getProgram(): Program
    {
        return $this->program;
    }
}
