<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Repositories\PlacePointRepository;
use App\Model\Settings\Settings;
use App\Services\QueryBus;
use Throwable;

/**
 * Komponenta s místem
 */
class PlaceContentControl extends BaseContentControl
{
    public function __construct(private QueryBus $queryBus, private PlacePointRepository $placePointRepository)
    {
    }

    /**
     * @throws Throwable
     */
    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content.latte');

        $template->heading     = $content->getHeading();
        $template->description = $this->queryBus->handle(new SettingStringValueQuery(Settings::PLACE_DESCRIPTION));
        $template->points      = $this->placePointRepository->findAll();

        $template->render();
    }

    public function renderScripts(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content_scripts.latte');
        $template->render();
    }
}
