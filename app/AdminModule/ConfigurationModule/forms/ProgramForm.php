<?php

namespace App\AdminModule\ConfigurationModule\Forms;


use App\AdminModule\Forms\BaseForm;
use App\Model\Enums\BasicBlockDuration;
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

        $basicBlockDurationOptions = $this->prepareBasicBlockDurationOptions();

        $form->addSelect('basicBlockDuration', 'admin.configuration.basic_block_duration', $basicBlockDurationOptions)
            ->addRule(Form::FILLED, 'admin.configuration.basic_block_duration_empty');
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
            'basicBlockDuration' => $this->settingsRepository->getValue('basic_block_duration'),
            'isAllowedAddBlock' => $this->settingsRepository->getValue('is_allowed_add_block'),
            'isAllowedModifySchedule' => $this->settingsRepository->getValue('is_allowed_modify_schedule'),
            'isAllowedRegisterPrograms' => $this->settingsRepository->getValue('is_allowed_register_programs'),
            'isAllowedRegisterProgramsBeforePayment' => $this->settingsRepository->getValue('is_allowed_register_programs_before_payment'),
            'registerProgramsFrom' => $this->settingsRepository->getDateTimeValue('register_programs_from'),
            'registerProgramsTo' => $this->settingsRepository->getDateTimeValue('register_programs_to')
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue('basic_block_duration', $values['basicBlockDuration']);
        $this->settingsRepository->setValue('is_allowed_add_block', $values['isAllowedAddBlock']);
        $this->settingsRepository->setValue('is_allowed_modify_schedule', $values['isAllowedModifySchedule']);
        $this->settingsRepository->setValue('is_allowed_register_programs', $values['isAllowedRegisterPrograms']);
        $this->settingsRepository->setValue('is_allowed_register_programs_before_payment', $values['isAllowedRegisterProgramsBeforePayment']);
        $this->settingsRepository->setDateTimeValue('register_programs_from', $values['registerProgramsFrom']);
        $this->settingsRepository->setDateTimeValue('register_programs_to', $values['registerProgramsTo']);
    }

    private function prepareBasicBlockDurationOptions() {
        $options = [];
        foreach (BasicBlockDuration::$durations as $duration)
            $options[$duration] = $this->translator->translate('admin.common.minutes', null, ['count' => $duration]);
        return $options;
    }

    public function validateSeminarFromDate($field, $args) {
        return $args[0] < $args[1];
    }

    public function validateSeminarToDate($field, $args) {
        return $args[0] > $args[1];
    }
}
