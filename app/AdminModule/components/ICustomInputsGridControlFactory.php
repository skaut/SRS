<?php

namespace App\AdminModule\Components;

interface ICustomInputsGridControlFactory
{
    /**
     * @return CustomInputsGridControl
     */
    function create();
}