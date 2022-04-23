<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\TextContentDto;

/**
 * Komponenta s textem.
 */
class TextContentControl extends BaseContentControl
{
    public function render(TextContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/text_content.latte');

        $template->heading = $content->getHeading();
        $template->text    = $content->getText();

        $template->render();
    }
}
