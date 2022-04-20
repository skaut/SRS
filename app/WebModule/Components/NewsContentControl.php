<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\NewsContentDto;
use App\Model\Cms\Repositories\NewsRepository;

/**
 * Komponenta s aktualitami
 */
class NewsContentControl extends BaseContentControl
{
    public function __construct(private NewsRepository $newsRepository)
    {
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
