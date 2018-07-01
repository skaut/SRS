<?php
declare(strict_types=1);

namespace App\WebModule\Components;

use Nette\Application\UI\Control;


/**
 * Komponenta s informací o pořadateli.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class OrganizerContentControl extends Control
{
    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/organizer_content.latte');

        $template->heading = $content->getHeading();
        $template->organizer = $content->getOrganizer();

        $template->render();
    }
}
