<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu se vstupenkou.
 */
interface ITicketContentControlFactory
{
    public function create(): TicketContentControl;
}
