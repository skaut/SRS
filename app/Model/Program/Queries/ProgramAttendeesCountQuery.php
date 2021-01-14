<?php

declare(strict_types=1);

namespace App\Model\Program\Queries;

use App\Model\Program\Program;

class ProgramAttendeesCountQuery
{
    private Program $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    public function getProgram(): Program
    {
        return $this->program;
    }
}
