<?php

namespace App\AdminModule\Components;

interface IProgramCategoriesGridControlFactory
{
    /**
     * @return ProgramCategoriesGridControl
     */
    function create();
}