<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\SlideshowContentDto;
use Nette\Application\UI\Control;

/**
 * Komponenta se slideshow.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SlideshowContentControl extends Control
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
