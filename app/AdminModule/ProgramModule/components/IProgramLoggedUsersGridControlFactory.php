<?php

namespace App\AdminModule\Components;

interface IProgramLoggedUsersGridControlFactory
{
    /**
     * @return ProgramLoggedUsersGridControl
     */
    function create();
}