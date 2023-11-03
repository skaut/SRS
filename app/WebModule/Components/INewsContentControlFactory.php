<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s aktualitami.
 */
interface INewsContentControlFactory
{
    public function create(): NewsContentControl;
}
