<?php

namespace App\AdminModule\Components;

interface IRoomsGridControlFactory
{
    /**
     * @return RoomsGridControl
     */
    function create();
}