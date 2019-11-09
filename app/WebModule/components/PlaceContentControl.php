<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\CMS\Content\ContentDTO;
use App\Model\Settings\Place\PlacePointRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Nette\Application\UI\Control;

/**
 * Komponenta s místem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceContentControl extends Control
{
    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var PlacePointRepository */
    private $placePointRepository;


    public function __construct(SettingsFacade $settingsFacade, PlacePointRepository $placePointRepository)
    {
        parent::__construct();

        $this->settingsFacade   = $settingsFacade;
        $this->placePointRepository = $placePointRepository;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function render(ContentDTO $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/place_content.latte');

        $template->heading     = $content->getHeading();
        $template->description = $this->settingsFacade->getValue(Settings::PLACE_DESCRIPTION);
        $template->points      = $this->placePointRepository->findAll();

        $template->render();
    }
}
