<?php

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\Settings\Place\PlacePointRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette\Application\UI\Control;

class PlaceContentControl extends Control
{
    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var PlacePointRepository */
    private $placePointRepository;

    public function __construct(SettingsRepository $settingsRepository, PlacePointRepository $placePointRepository)
    {
        parent::__construct();

        $this->settingsRepository = $settingsRepository;
        $this->placePointRepository = $placePointRepository;
    }

    public function render($content)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content.latte');

        $template->heading = $content->getHeading();
        $template->description = $this->settingsRepository->getValue(Settings::PLACE_DESCRIPTION);
        $template->points = $this->placePointRepository->findAll();

        $template->render();
    }
}