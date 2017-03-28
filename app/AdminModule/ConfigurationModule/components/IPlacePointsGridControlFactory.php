<?php

namespace App\AdminModule\ConfigurationModule\Components;


/**
 * Factory komponenty pro správu mapových bodů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPlacePointsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return PlacePointsGridControl
     */
    function create();
}