<?php

namespace App\AdminModule\Components;

interface IUsersGridControlFactory
{
    /**
     * @return UsersGridControl
     */
    function create();
}