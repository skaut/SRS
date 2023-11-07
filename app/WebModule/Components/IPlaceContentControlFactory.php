<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s místem.
 */
interface IPlaceContentControlFactory
{
    public function create(): PlaceContentControl;
}
