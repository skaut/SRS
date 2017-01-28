<?php

namespace App\AdminModule\Forms;

class ProgramConfigurationFormFactory
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

        $form->addSelect('basicBlockDuration', 'admin.configuration.basic_block_duration');
        $form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');
        $form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');
        $form->addCheckbox('isAllowedLogInPrograms', 'admin.configuration.is_allowed_log_in_programs');
        $form->addCheckbox('isAllowedLogInProgramsBeforePayment', 'admin.configuration.is_allowed_log_in_programs_before_payment');
        $form->addDateTimePicker('logInProgramsFrom', 'admin.configuration.log_in_programs_from');
        $form->addDateTimePicker('logInProgramsTo', 'admin.configuration.log_in_programs_to');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
