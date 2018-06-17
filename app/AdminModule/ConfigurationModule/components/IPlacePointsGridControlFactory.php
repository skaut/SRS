<?php
declare(strict_types=1);

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
    public function create();
}
