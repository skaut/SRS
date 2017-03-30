<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s kapacitami rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICapacitiesContentControlFactory
{
    /**
     * @return CapacitiesContentControl
     */
    public function create();
}
