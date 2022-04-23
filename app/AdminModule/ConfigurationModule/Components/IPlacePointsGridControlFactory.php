<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

/**
 * Factory komponenty pro správu mapových bodů.
 */
interface IPlacePointsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): PlacePointsGridControl;
}
