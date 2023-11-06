<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty se vstupenkou.
 */
interface ITicketControlFactory
{
    public function create(): TicketControl;
}
