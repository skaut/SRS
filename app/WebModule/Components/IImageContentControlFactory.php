<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s obrázkem.
 */
interface IImageContentControlFactory
{
    public function create(): ImageContentControl;
}
