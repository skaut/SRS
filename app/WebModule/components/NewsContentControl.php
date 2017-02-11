<?php

namespace App\WebModule\Components;

use App\Model\CMS\NewsRepository;
use Nette\Application\UI\Control;

class NewsContentControl extends Control
{
    /** @var NewsRepository */
    private $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        parent::__construct();

        $this->newsRepository = $newsRepository;
    }

    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/news_content.latte');

        $template->heading = $content->getHeading();
        $template->news = $this->newsRepository->findPublishedOrderedByDate($content->getCount());

        $template->render();
    }
}