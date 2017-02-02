<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Services\SkautIsService;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;

class SkautIsEventFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    /**
     * @var SkautIsService
     */
    private $skautIsService;

    public function __construct(BaseFormFactory $baseFormFactory, SkautIsService $skautIsService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->skautIsService = $skautIsService;
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
            $skautIsEvents = $this->skautIsService->getDraftEvents();
            foreach ($skautIsEvents as $e)
                $choices[$e->ID] = $e->DisplayName;
        } catch (WsdlException $ex) { }
        return $choices;
    }
}
