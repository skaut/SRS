<?php

namespace App\AdminModule\Forms;

use Nette\Application\UI\Form;

class SkautIsActionConfigurationFormFactory
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

        $form->addSelect('skautisAction', 'admin.configuration.skautis_action')
            ->addRule(Form::FILLED, 'admin.configuration.skautis_action_empty');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
