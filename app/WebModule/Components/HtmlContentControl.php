<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\HtmlContentDto;
use Nette\Application\UI\Control;

/**
 * Komponenta s HTML.
 */
class HtmlContentControl extends BaseContentControl
{
    public function render(HtmlContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/html_content.latte');

        $template->heading = $content->getHeading();
        $template->html    = $content->getText();

        $template->render();
    }
}
