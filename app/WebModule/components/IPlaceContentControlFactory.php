<?php

namespace App\WebModule\Components;


interface IPlaceContentControlFactory
{
    /**
     * @return PlaceContentControl
     */
    function create();
}