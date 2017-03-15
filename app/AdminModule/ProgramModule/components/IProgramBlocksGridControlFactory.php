<?php

namespace App\AdminModule\ProgramModule\Components;


interface IProgramBlocksGridControlFactory
{
    /**
     * @return ProgramBlocksGridControl
     */
    function create();
}