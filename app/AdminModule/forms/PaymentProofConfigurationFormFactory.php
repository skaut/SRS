<?php

namespace App\AdminModule\Forms;

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

        $form->addTextArea('company', 'admin.configuration.company');
        $form->addText('ico', 'admin.configuration.ico');
        $form->addText('accountant', 'admin.configuration.accountant');
        $form->addText('printLocation', 'admin.configuration.print_location');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
