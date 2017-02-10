<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\SkautIsEventForm;
use App\AdminModule\ConfigurationModule\Forms\SkautIsEventFormFactory;
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
        $eventId = $this->settingsRepository->getValue('skautis_event_id');
        if ($eventId !== null) {
            $this->template->event = $this->settingsRepository->getValue('skautis_event_name');
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
        $this->settingsRepository->setValue('skautis_event_id', null);
        $this->settingsRepository->setValue('skautis_event_name', null);

        $this->flashMessage('admin.configuration.skautis_event_disconnect_successful', 'success');

        $this->redirect('this');
    }

    public function handleSyncParticipants()
    {
        $participants = $this->userRepository->findAllSyncedWithSkautIS();

        $eventId = $this->settingsRepository->getValue('skautis_event_id');

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