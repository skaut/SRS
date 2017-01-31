<?php

namespace App\AdminModule\ProgramModule\Components;

interface IProgramNotLoggedUsersGridControlFactory
{
    /**
     * @return ProgramNotLoggedUsersGridControl
     */
    function create();
}