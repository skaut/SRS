<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\ImageContentDto;
use Nette\Application\UI\Control;

/**
 * Komponenta s obrázkem.
 */
class ImageContentControl extends Control
{
    public function render(ImageContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/image_content.latte');

        $template->heading = $content->getHeading();
        $template->image   = $content->getImage();
        $template->align   = $content->getAlign();
        $template->width   = $content->getWidth();
        $template->height  = $content->getHeight();

        $template->render();
    }
}
