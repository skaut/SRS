<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu se slideshow.
 */
interface ISlideshowContentControlFactory
{
    public function create(): SlideshowContentControl;
}
