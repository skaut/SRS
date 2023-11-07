<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s kapacitami rolí.
 */
interface ICapacitiesContentControlFactory
{
    public function create(): CapacitiesContentControl;
}
