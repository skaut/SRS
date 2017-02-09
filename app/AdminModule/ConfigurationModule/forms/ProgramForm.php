<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;

class ProgramForm extends Nette\Object
{
    /**
     * @var BaseForm
     */
    private $baseForm;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(BaseForm $baseForm, Translator $translator)
    {
        $this->baseForm = $baseForm;
        $this->translator = $translator;
    }

    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $basicBlockDurationOptions = $this->prepareBasicBlockDurationOptions([5, 10, 15, 30, 45, 60, 75, 90, 105, 120]);

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

        return $form;
    }

    private function prepareBasicBlockDurationOptions($durations) {
        $options = [];
        foreach ($durations as $duration)
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
