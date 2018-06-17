<?php
declare(strict_types=1);

namespace App\WebModule\Components;

use Nette\Application\UI\Control;


/**
 * Komponenta s HTML.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HtmlContentControl extends Control
{
    /**
     * @param $content
     */
    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/html_content.latte');

        $template->heading = $content->getHeading();
        $template->html = $content->getText();

        $template->render();
    }
}
