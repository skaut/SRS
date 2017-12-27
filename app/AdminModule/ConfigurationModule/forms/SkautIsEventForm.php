<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
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
    private $baseFormFactory;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var SkautIsEventGeneralService */
    private $skautIsEventGeneralService;

    /** @var SkautIsEventEducationService */
    private $skautIsEventEducationService;


    /**
     * SkautIsEventForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     * @param SkautIsEventGeneralService $skautIsEventGeneralService
     * @param SkautIsEventEducationService $skautIsEventEducationService
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository,
                                SkautIsEventGeneralService $skautIsEventGeneralService,
                                SkautIsEventEducationService $skautIsEventEducationService)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->skautIsEventGeneralService = $skautIsEventGeneralService;
        $this->skautIsEventEducationService = $skautIsEventEducationService;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $eventTypeSelect = $form->addSelect('skautisEventType', 'admin.configuration.skautis_event_type',
            SkautIsEventType::getSkautIsEventTypesOptions());
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::GENERAL)
            ->toggle('event-general');
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::EDUCATION)
            ->toggle('event-education');

        $form->addSelect('skautisEventGeneral', 'admin.configuration.skautis_event',
            $this->skautIsEventGeneralService->getEventsOptions())
            ->setOption('id', 'event-general');

        $form->addSelect('skautisEventEducation', 'admin.configuration.skautis_event',
            $this->skautIsEventEducationService->getEventsOptions())
            ->setOption('id', 'event-education');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'skautisEventType' => $this->settingsRepository->getValue(Settings::SKAUTIS_EVENT_TYPE)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \App\Model\Settings\SettingsException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $eventId = NULL;
        $eventName = NULL;
        $eventType = $values['skautisEventType'];

        switch ($eventType) {
            case SkautIsEventType::GENERAL:
                $eventId = $values['skautisEventGeneral'];
                $eventName = $this->skautIsEventGeneralService->getEventDisplayName($eventId);
                break;

            case SkautIsEventType::EDUCATION:
                $eventId = $values['skautisEventEducation'];
                $eventName = $this->skautIsEventEducationService->getEventDisplayName($eventId);
                break;
        }

        $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_TYPE, $eventType);

        if ($eventId !== NULL) {
            $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_ID, $eventId);
            $this->settingsRepository->setValue(Settings::SKAUTIS_EVENT_NAME, $eventName);
        }
    }
}
