<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\PlaceContent;
use App\Model\Settings\Place\PlacePointRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use Nette\Application\UI\Control;

/**
 * Komponenta s místem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceContentControl extends Control
{
    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var PlacePointRepository */
    private $placePointRepository;


    public function __construct(SettingsRepository $settingsRepository, PlacePointRepository $placePointRepository)
    {
        parent::__construct();

        $this->settingsRepository   = $settingsRepository;
        $this->placePointRepository = $placePointRepository;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function render(PlaceContent $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content.latte');

        $template->heading     = $content->getHeading();
        $template->description = $this->settingsRepository->getValue(Settings::PLACE_DESCRIPTION);
        $template->points      = $this->placePointRepository->findAll();

        $template->render();
    }
}
