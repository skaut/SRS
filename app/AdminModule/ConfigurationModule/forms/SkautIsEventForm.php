<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Services\SkautIsService;
use Nette;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;

class SkautIsEventForm extends Nette\Object
{
    /**
     * @var BaseForm
     */
    private $baseForm;

    /**
     * @var SkautIsService
     */
    private $skautIsService;

    public function __construct(BaseForm $baseForm, SkautIsService $skautIsService)
    {
        $this->baseForm = $baseForm;
        $this->skautIsService = $skautIsService;
    }

    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addSelect('skautisEvent', 'admin.configuration.skautis_event', $this->skautIsService->getEventsOptions())
            ->addRule(Form::FILLED, 'admin.configuration.skautis_event_empty');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
