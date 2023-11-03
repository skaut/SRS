<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use Nette\Application\UI\Control;

/**
 * Komponenta se vstupenkou.
 */
class TicketControl extends Control
{
    public function render(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ticket_content.latte');



        $template->render();
    }
}
