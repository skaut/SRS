<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s informací o pořadateli.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IOrganizerContentControlFactory
{
    public function create() : OrganizerContentControl;
}
