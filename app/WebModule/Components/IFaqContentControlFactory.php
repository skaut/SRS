<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s FAQ.
 */
interface IFaqContentControlFactory
{
    public function create(): FaqContentControl;
}
