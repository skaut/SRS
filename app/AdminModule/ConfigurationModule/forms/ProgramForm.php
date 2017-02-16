<?php

namespace App\AdminModule\ConfigurationModule\Forms;


use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;

class ProgramForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var  SettingsRepository */
    private $settingsRepository;

    /** @var Translator */
    private $translator;

    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository, Translator $translator)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->translator = $translator;
    }

    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');
        $form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');
        $form->addCheckbox('isAllowedRegisterPrograms', 'admin.configuration.is_allowed_register_programs');
        $form->addCheckbox('isAllowedRegisterProgramsBeforePayment', 'admin.configuration.is_allowed_register_programs_before_payment');

        $registerProgramsFrom = $form->addDateTimePicker('registerProgramsFrom', 'admin.configuration.register_programs_from')
            ->addRule(Form::FILLED, 'admin.configuration.register_programs_from_empty');
        $registerProgramsTo = $form->addDateTimePicker('registerProgramsTo', 'admin.configuration.register_programs_to')
            ->addRule(Form::FILLED, 'admin.configuration.register_programs_to_empty');

        $registerProgramsFrom->addRule([$this, 'validateSeminarFromDate'], 'admin.configuration.register_programs_from_after_to', [$registerProgramsFrom, $registerProgramsTo]);
        $registerProgramsTo->addRule([$this, 'validateSeminarToDate'], 'admin.configuration.register_programs_to_before_from', [$registerProgramsTo, $registerProgramsFrom]);

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'isAllowedAddBlock' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK),
            'isAllowedModifySchedule' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE),
            'isAllowedRegisterPrograms' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS),
            'isAllowedRegisterProgramsBeforePayment' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT),
            'registerProgramsFrom' => $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM),
            'registerProgramsTo' => $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_ADD_BLOCK, $values['isAllowedAddBlock']);
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE, $values['isAllowedModifySchedule']);
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS, $values['isAllowedRegisterPrograms']);
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, $values['isAllowedRegisterProgramsBeforePayment']);
        $this->settingsRepository->setDateTimeValue(Settings::REGISTER_PROGRAMS_FROM, $values['registerProgramsFrom']);
        $this->settingsRepository->setDateTimeValue(Settings::REGISTER_PROGRAMS_TO, $values['registerProgramsTo']);
    }

    public function validateSeminarFromDate($field, $args) {
        return $args[0] < $args[1];
    }

    public function validateSeminarToDate($field, $args) {
        return $args[0] > $args[1];
    }
}
