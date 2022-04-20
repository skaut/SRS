<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s FAQ
 */
interface IFaqContentControlFactory
{
    public function create(): FaqContentControl;
}
