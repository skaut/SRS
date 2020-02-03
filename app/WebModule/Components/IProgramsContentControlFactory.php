<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s výběrem programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IProgramsContentControlFactory
{
    public function create() : ProgramsContentControl;
}
