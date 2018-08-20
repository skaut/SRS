<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\TextContent;
use Nette\Application\UI\Control;

/**
 * Komponenta s textem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextContentControl extends Control
{
    public function render(TextContent $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/text_content.latte');

        $template->heading = $content->getHeading();
        $template->text    = $content->getText();

        $template->render();
    }
}
