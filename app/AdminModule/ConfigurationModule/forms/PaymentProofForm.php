<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;

class PaymentProofForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var SettingsRepository */
    private $settingsRepository;

    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    public function create()
    {
        $form = $this->baseForm->create();

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

        $form->setDefaults([
            'company' => $this->settingsRepository->getValue('company'),
            'ico' => $this->settingsRepository->getValue('ico'),
            'accountant' => $this->settingsRepository->getValue('accountant'),
            'printLocation' => $this->settingsRepository->getValue('print_location')
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue('company', $values['company']);
        $this->settingsRepository->setValue('ico', $values['ico']);
        $this->settingsRepository->setValue('accountant', $values['accountant']);
        $this->settingsRepository->setValue('print_location', $values['printLocation']);
    }
}
