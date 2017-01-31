<?php

namespace App\AdminModule\ProgramModule\Components;

interface IProgramCategoriesGridControlFactory
{
    /**
     * @return ProgramCategoriesGridControl
     */
    function create();
}