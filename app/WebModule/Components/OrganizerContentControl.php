<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\OrganizerContentDto;

/**
 * Komponenta s informací o pořadateli
 */
class OrganizerContentControl extends BaseContentControl
{
    public function render(OrganizerContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/organizer_content.latte');

        $template->heading   = $content->getHeading();
        $template->organizer = $content->getOrganizer();

        $template->render();
    }
}
