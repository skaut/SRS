<?php

namespace App\AdminModule\ProgramModule\Components;


interface IProgramAttendeesGridControlFactory
{
    /**
     * @return ProgramAttendeesGridControl
     */
    function create();
}