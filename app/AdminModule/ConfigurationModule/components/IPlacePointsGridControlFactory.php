<?php

namespace App\AdminModule\ConfigurationModule\Components;


interface IPlacePointsGridControlFactory
{
    /**
     * @return PlacePointsGridControl
     */
    function create();
}