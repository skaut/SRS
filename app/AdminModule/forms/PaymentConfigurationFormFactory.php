<?php

namespace App\AdminModule\Forms;

class PaymentConfigurationFormFactory
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

        $form->addText('accountNumber', 'admin.configuration.account_number');
        $form->addText('variableSymbolCode', 'admin.configuration.variable_symbol_code');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
