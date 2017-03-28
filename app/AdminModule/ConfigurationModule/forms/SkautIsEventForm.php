<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Services\SkautIsService;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulár pro nastavení propojení se skautIS akcí.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var SkautIsService */
    private $skautIsService;


    /**
     * SkautIsEventForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     * @param SkautIsService $skautIsService
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository, SkautIsService $skautIsService)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->skautIsService = $skautIsService;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     */
    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addSelect('skautisEvent', 'admin.configuration.skautis_event', $this->skautIsService->getEventsOptions())
            ->addRule(Form::FILLED, 'admin.configuration.skautis_event_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $eventId = $values['skautisEvent'];

        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_ID, $eventId);
        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_NAME, $this->skautIsService->getEventDisplayName($eventId));
    }
}
