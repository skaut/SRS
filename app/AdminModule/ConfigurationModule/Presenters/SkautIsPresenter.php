<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\ISkautIsEventEducationGridControlFactory;
use App\AdminModule\ConfigurationModule\Components\SkautIsEventEducationGridControl;
use App\AdminModule\ConfigurationModule\Forms\SkautIsEventFormFactory;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\SkautIs\SkautIsCourseRepository;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení propojení se skautIS akcí.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SkautIsEventFormFactory
     * @inject
     */
    public $skautIsEventFormFactory;

    /**
     * @var ISkautIsEventEducationGridControlFactory
     * @inject
     */
    public $skautISEventEducationGridControlFactory;

    /**
     * @var SkautIsCourseRepository
     * @inject
     */
    public $skautIsCourseRepository;

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDefault() : void
    {
        $eventId = $this->settingsService->getValue(Settings::SKAUTIS_EVENT_ID);
        if ($eventId !== null) {
            $this->template->event          = $this->settingsService->getValue(Settings::SKAUTIS_EVENT_NAME);
            $this->template->connected      = true;
            $this->template->eventEducation = $this->settingsService->getValue(Settings::SKAUTIS_EVENT_TYPE) === SkautIsEventType::EDUCATION;
        } else {
            $this->template->connected = false;
        }
    }

    /**
     * Zruší propojení s akcí ve skautIS.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDisconnect() : void
    {
        $this->settingsService->setValue(Settings::SKAUTIS_EVENT_ID, null);
        $this->settingsService->setValue(Settings::SKAUTIS_EVENT_NAME, null);

        if ($this->settingsService->getValue(Settings::SKAUTIS_EVENT_TYPE) === SkautIsEventType::EDUCATION) {
            $this->skautIsCourseRepository->removeAll();
        }

        $this->flashMessage('admin.configuration.skautis_event_disconnect_successful', 'success');

        $this->redirect('this');
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentSkautIsEventForm() : Form
    {
        $form = $this->skautIsEventFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.skautis_event_connect_successful', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentSkautIsEventEducationGrid() : SkautIsEventEducationGridControl
    {
        return $this->skautISEventEducationGridControlFactory->create();
    }
}
