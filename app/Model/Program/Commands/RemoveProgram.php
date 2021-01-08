<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Program;

class RemoveProgram
{
    private Program $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    public function getProgram() : Program
    {
        return $this->program;
    }
}
