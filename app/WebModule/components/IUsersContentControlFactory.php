<?php

namespace App\WebModule\Components;


interface IUsersContentControlFactory
{
    /**
     * @return UsersContentControl
     */
    function create();
}