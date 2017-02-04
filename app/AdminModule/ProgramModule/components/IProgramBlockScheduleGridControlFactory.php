<?php

namespace App\AdminModule\ProgramModule\Components;

interface IProgramBlockScheduleGridControlFactory
{
    /**
     * @return ProgramBlockScheduleGridControl
     */
    function create();
}