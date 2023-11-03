<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\ContentDto;

/**
 * Komponenta obsahu se vstupenkou.
 */
class TicketContentControl extends BaseContentControl
{
    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/ticket_content.latte');

        $template->heading = $content->getHeading();

        $template->render();
    }
}
