<?php

namespace App\AdminModule\Components;

interface IProgramNotLoggedUsersGridControlFactory
{
    /**
     * @return ProgramNotLoggedUsersGridControl
     */
    function create();
}