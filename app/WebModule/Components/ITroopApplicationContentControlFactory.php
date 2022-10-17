<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s přihláškou oddílu.
 */
interface ITroopApplicationContentControlFactory
{
    public function create(): ApplicationContentControl;
}
