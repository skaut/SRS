<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Program;

class SaveProgram
{
    public function __construct(private Program $program)
    {
    }

    public function getProgram(): Program
    {
        return $this->program;
    }
}
