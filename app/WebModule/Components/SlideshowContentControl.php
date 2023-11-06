<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\SlideshowContentDto;

/**
 * Komponenta obsahu se slideshow.
 */
class SlideshowContentControl extends BaseContentControl
{
    public function render(SlideshowContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/slideshow_content.latte');

        $template->heading = $content->getHeading();
        $template->images  = $content->getImages();

        $template->render();
    }
}
