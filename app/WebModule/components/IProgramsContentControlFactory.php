<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s výběrem programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IProgramsContentControlFactory
{
    /**
     * @return ProgramsContentControl
     */
    function create();
}