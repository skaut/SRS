<?php

namespace App\WebModule\Components;


interface IImageContentControlFactory
{
    /**
     * @return ImageContentControl
     */
    function create();
}