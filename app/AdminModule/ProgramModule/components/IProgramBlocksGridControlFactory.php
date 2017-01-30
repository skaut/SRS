<?php

namespace App\AdminModule\Components;

interface IProgramBlocksGridControlFactory
{
    /**
     * @return ProgramBlocksGridControl
     */
    function create();
}