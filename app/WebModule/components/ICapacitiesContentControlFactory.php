<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s kapacitami rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICapacitiesContentControlFactory
{
    /**
     * @return CapacitiesContentControl
     */
    function create();
}