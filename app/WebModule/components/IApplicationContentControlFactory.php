<?php

namespace App\WebModule\Components;


interface IApplicationContentControlFactory
{
    /**
     * @return ApplicationContentControl
     */
    function create();
}