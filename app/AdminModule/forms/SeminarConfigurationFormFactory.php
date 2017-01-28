<?php

namespace App\AdminModule\Forms;

use Nette\Application\UI\Form;

class SeminarConfigurationFormFactory
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

        $form->addText('seminarName', 'admin.configuration.seminar_name')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_name_empty');

        $form->addDatePicker('seminarFromDate', 'admin.configuration.seminar_from_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_from_date_empty');

        $form->addDatePicker('seminarToDate', 'admin.configuration.seminar_to_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_to_date_empty');

        $form->addDatePicker('editRegistrationTo', 'admin.configuration.edit_registration_to')
            ->addRule(Form::FILLED, 'admin.configuration.edit_registration_to_empty');

        $form->addText('seminarEmail', 'admin.configuration.seminar_email')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_email_empty')
            ->addRule(Form::EMAIL, 'admin.configuration.seminar_email_format');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
