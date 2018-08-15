<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\NewsRepository;
use Nette\Application\UI\Control;

/**
 * Komponenta s aktualitami.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsContentControl extends Control
{
    /** @var NewsRepository */
    private $newsRepository;


    public function __construct(NewsRepository $newsRepository)
    {
        parent::__construct();

        $this->newsRepository = $newsRepository;
    }

    public function render($content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/news_content.latte');

        $template->heading = $content->getHeading();
        $template->news    = $this->newsRepository->findPublishedOrderedByPinnedAndDate($content->getCount());

        $template->render();
    }
}
