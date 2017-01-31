<?php

namespace App\AdminModule\ProgramModule\Components;

interface IProgramLoggedUsersGridControlFactory
{
    /**
     * @return ProgramLoggedUsersGridControl
     */
    function create();
}