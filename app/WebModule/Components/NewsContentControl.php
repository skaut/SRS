<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\NewsContentDto;
use App\Model\Cms\Repositories\NewsRepository;
use Nette\Application\UI\Control;

/**
 * Komponenta s aktualitami.
 */
class NewsContentControl extends Control
{
    private NewsRepository $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    public function render(NewsContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/news_content.latte');

        $template->heading = $content->getHeading();
        $template->news    = $this->newsRepository->findPublishedOrderedByPinnedAndDate($content->getCount());

        $template->render();
    }
}
