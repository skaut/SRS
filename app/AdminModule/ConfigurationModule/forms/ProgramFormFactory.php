<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use Nette\Application\UI\Form;

class ProgramFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $basicBlockDurationChoices = $this->prepareBasicBlockDurationChoices([5, 10, 15, 30, 45, 60, 75, 90, 105, 120], $form->getTranslator());

        $form->addSelect('basicBlockDuration', 'admin.configuration.basic_block_duration', $basicBlockDurationChoices)
            ->addRule(Form::FILLED, 'admin.configuration.basic_block_duration_empty');
        $form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');
        $form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');
        $form->addCheckbox('isAllowedLogInPrograms', 'admin.configuration.is_allowed_log_in_programs');
        $form->addCheckbox('isAllowedLogInProgramsBeforePayment', 'admin.configuration.is_allowed_log_in_programs_before_payment');

        $logInProgramsFrom = $form->addDateTimePicker('logInProgramsFrom', 'admin.configuration.log_in_programs_from')
            ->addRule(Form::FILLED, 'admin.configuration.log_in_programs_from_empty');
        $logInProgramsTo = $form->addDateTimePicker('logInProgramsTo', 'admin.configuration.log_in_programs_to')
            ->addRule(Form::FILLED, 'admin.configuration.log_in_programs_to_empty');

        $logInProgramsFrom->addRule([$this, 'validateSeminarFromDate'], 'admin.configuration.log_in_programs_from_after_to', [$logInProgramsFrom, $logInProgramsTo]);
        $logInProgramsTo->addRule([$this, 'validateSeminarToDate'], 'admin.configuration.log_in_programs_to_before_from', [$logInProgramsTo, $logInProgramsFrom]);


        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }

    private function prepareBasicBlockDurationChoices($durations, $translator) {
        $choices = [];
        foreach ($durations as $duration)
            $choices[$duration] = $translator->translate('admin.common.minutes', null, ['count' => $duration]);
        return $choices;
    }

    public function validateSeminarFromDate($field, $args) {
        return $args[0] < $args[1];
    }

    public function validateSeminarToDate($field, $args) {
        return $args[0] > $args[1];
    }
}
