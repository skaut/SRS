<?php

namespace App\AdminModule\ProgramModule\Components;

interface IRoomsGridControlFactory
{
    /**
     * @return RoomsGridControl
     */
    function create();
}