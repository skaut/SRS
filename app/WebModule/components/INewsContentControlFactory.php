<?php

namespace App\WebModule\Components;


interface INewsContentControlFactory
{
    /**
     * @return NewsContentControl
     */
    function create();
}