<?php

namespace App\AdminModule\Forms;

use Nette\Application\UI\Form;

class PaymentProofConfigurationFormFactory
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

        $form->addTextArea('company', 'admin.configuration.company')
            ->addRule(Form::FILLED, 'admin.configuration.company_empty');

        $form->addText('ico', 'admin.configuration.ico')
            ->addRule(Form::FILLED, 'admin.configuration.ico_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.ico_format', '^\d{8}$');

        $form->addText('accountant', 'admin.configuration.accountant')
            ->addRule(Form::FILLED, 'admin.configuration.accountant_empty');

        $form->addText('printLocation', 'admin.configuration.print_location')
            ->addRule(Form::FILLED, 'admin.configuration.print_location_empty');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
