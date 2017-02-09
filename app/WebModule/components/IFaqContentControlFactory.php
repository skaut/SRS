<?php

namespace App\WebModule\Components;


interface IFaqContentControlFactory
{
    /**
     * @return FaqContentControl
     */
    function create();
}