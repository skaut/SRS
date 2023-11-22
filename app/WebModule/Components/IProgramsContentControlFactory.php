<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s výběrem programů.
 */
interface IProgramsContentControlFactory
{
    public function create(): ProgramsContentControl;
}
