<?php

namespace App\AdminModule\Forms;

use Nette\Application\UI\Form;
use Skautis\Skautis;

class SkautIsEventConfigurationFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    /**
     * @var Skautis
     */
    private $skautIS;

    public function __construct(BaseFormFactory $baseFormFactory, Skautis $skautis)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->skautIS = $skautis;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $eventsChoices = $this->prepareEventsChoices();

        $form->addSelect('skautisEvent', 'admin.configuration.skautis_event', $eventsChoices)
            ->addRule(Form::FILLED, 'admin.configuration.skautis_event_empty');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }

    private function prepareEventsChoices()
    {
        $choices = [];
        try {
            $skautIsEvents = $this->skautIS->event->EventGeneralAll([
                'ID_Login' => $this->skautIS->getUser()->getLoginId(),
                'ID_EventGeneralState' => 'draft'
            ]);
            foreach ($skautIsEvents as $e)
                $choices[$e->ID] = $e->DisplayName;
        } catch (\Skautis\Wsdl\WsdlException $ex) { }
        return $choices;
    }
}
