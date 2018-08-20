<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\OrganizerContent;
use Nette\Application\UI\Control;

/**
 * Komponenta s informací o pořadateli.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class OrganizerContentControl extends Control
{
    public function render(OrganizerContent $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/organizer_content.latte');

        $template->heading   = $content->getHeading();
        $template->organizer = $content->getOrganizer();

        $template->render();
    }
}
