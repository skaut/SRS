<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s informací o pořadateli.
 */
interface IOrganizerContentControlFactory
{
    public function create(): OrganizerContentControl;
}
