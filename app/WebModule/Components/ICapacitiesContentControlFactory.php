<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s kapacitami rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICapacitiesContentControlFactory
{
    public function create(): CapacitiesContentControl;
}
