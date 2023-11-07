<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Program;

class RemoveProgram
{
    public function __construct(private readonly Program $program)
    {
    }

    public function getProgram(): Program
    {
        return $this->program;
    }
}
