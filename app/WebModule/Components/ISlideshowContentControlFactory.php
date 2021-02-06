<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty se slideshow.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ISlideshowContentControlFactory
{
    public function create(): SlideshowContentControl;
}
