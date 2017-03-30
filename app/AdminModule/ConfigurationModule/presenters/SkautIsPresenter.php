<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\SkautIsEventForm;
use App\Model\Settings\Settings;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;


/**
 * Presenter obsluhující nastavení propojení se skautIS akcí.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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
        if ($eventId !== NULL) {
            $this->template->event = $this->settingsRepository->getValue(Settings::SKAUTIS_EVENT_NAME);
            $this->template->connected = TRUE;
            $this->template->access = TRUE;
            $this->template->closed = FALSE;

            try {
                if (!$this->skautIsService->isEventDraft($eventId))
                    $this->template->closed = TRUE;
            } catch (WsdlException $ex) {
                $this->template->access = FALSE;
            }
        } else {
            $this->template->connected = FALSE;
        }
    }

    /**
     * Zruší propojení s akcí ve skautIS.
     */
    public function handleDisconnect()
    {
        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_ID, NULL);
        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_NAME, NULL);

        $this->flashMessage('admin.configuration.skautis_event_disconnect_successful', 'success');

        $this->redirect('this');
    }

    /**
     * Synchronizuje účastníky s účastníky ve skautIS.
     */
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

    protected function createComponentSkautIsEventForm()
    {
        $form = $this->skautIsEventFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.skautis_event_connect_successful', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
