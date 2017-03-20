<?php

namespace App\WebModule\Components;

use Nette\Application\UI\Control;


/**
 * Komponenta s textem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextContentControl extends Control
{
    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/text_content.latte');

        $template->heading = $content->getHeading();
        $template->text = $content->getText();

        $template->render();
    }
}