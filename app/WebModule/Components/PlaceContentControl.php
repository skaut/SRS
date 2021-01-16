<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Repositories\PlacePointRepository;
use App\Model\Settings\Settings;
use App\Services\ISettingsService;
use Nette\Application\UI\Control;
use Throwable;

/**
 * Komponenta s místem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceContentControl extends Control
{
    private ISettingsService $settingsService;

    private PlacePointRepository $placePointRepository;

    public function __construct(ISettingsService $settingsService, PlacePointRepository $placePointRepository)
    {
        $this->settingsService      = $settingsService;
        $this->placePointRepository = $placePointRepository;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content.latte');

        $template->heading     = $content->getHeading();
        $template->description = $this->settingsService->getValue(Settings::PLACE_DESCRIPTION);
        $template->points      = $this->placePointRepository->findAll();

        $template->render();
    }
}
