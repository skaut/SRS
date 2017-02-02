<?php

namespace App\AdminModule\ConfigurationModule\Components;

interface ICustomInputsGridControlFactory
{
    /**
     * @return CustomInputsGridControl
     */
    function create();
}