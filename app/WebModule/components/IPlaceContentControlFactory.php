<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s místem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPlaceContentControlFactory
{
    /**
     * @return PlaceContentControl
     */
    function create();
}