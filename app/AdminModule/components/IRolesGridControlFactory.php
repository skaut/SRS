<?php

namespace App\AdminModule\Components;


interface IRolesGridControlFactory
{
    /**
     * @return RolesGridControl
     */
    function create();
}