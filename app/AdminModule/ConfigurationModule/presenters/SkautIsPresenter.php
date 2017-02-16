<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\SkautIsEventForm;
use App\AdminModule\ConfigurationModule\Forms\SkautIsEventFormFactory;
use App\Model\Settings\Settings;
use App\Services\SkautIsService;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;

class SkautIsPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SkautIsEventForm
     * @inject
     */
    public $skautIsEventFormFactory;

    public function renderDefault()
    {
        $eventId = $this->settingsRepository->getValue(Settings::SKAUTIS_EVENT_ID);
        if ($eventId !== null) {
            $this->template->event = $this->settingsRepository->getValue(Settings::SKAUTIS_EVENT_NAME);
            $this->template->connected = true;
            $this->template->access = true;
            $this->template->closed = false;

            try {
                if (!$this->skautIsService->isEventDraft($eventId))
                    $this->template->closed = true;
            } catch (WsdlException $ex) {
                $this->template->access = false;
            }
        }
        else {
            $this->template->connected = false;
        }
    }

    public function handleDisconnect()
    {
        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_ID, null);
        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_NAME, null);

        $this->flashMessage('admin.configuration.skautis_event_disconnect_successful', 'success');

        $this->redirect('this');
    }

    public function handleSyncParticipants()
    {
        $participants = $this->userRepository->findAllSyncedWithSkautIS();

        $eventId = $this->settingsRepository->getValue(Settings::SKAUTIS_EVENT_ID);

        try {
            $this->skautIsService->syncParticipants($eventId, $participants);
            $this->flashMessage('admin.configuration.skautis_event_sync_successful', 'success');
        } catch (WsdlException $ex) {
            $this->flashMessage('admin.configuration.skautis_event_sync_unsuccessful', 'danger');
        }

        $this->redirect('this');
    }

    protected function createComponentSkautIsEventForm($name)
    {
        $form = $this->skautIsEventFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.skautis_event_connect_successful', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}