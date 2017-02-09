<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;

class PaymentForm extends Nette\Object
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

        $form->addText('accountNumber', 'admin.configuration.account_number')
            ->addRule(Form::FILLED, 'admin.configuration.account_number_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.account_number_format', '^(\d{1,6}-|)\d{2,10}\/\d{4}$');

        $form->addText('variableSymbolCode', 'admin.configuration.variable_symbol_code', 2)
            ->addRule(Form::FILLED, 'admin.configuration.variable_symbol_code_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.variable_symbol_code_format', '^\d{2}$');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'accountNumber' => $this->settingsRepository->getValue('account_number'),
            'variableSymbolCode' => $this->settingsRepository->getValue('variable_symbol_code')
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue('account_number', $values['accountNumber']);
        $this->settingsRepository->setValue('variable_symbol_code', $values['variableSymbolCode']);
    }
}
