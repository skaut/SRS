<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s místem
 */
interface IPlaceContentControlFactory
{
    public function create(): PlaceContentControl;
}
