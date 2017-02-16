<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;

class PaymentForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository, UserRepository $userRepository)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
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
            'accountNumber' => $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER),
            'variableSymbolCode' => $this->settingsRepository->getValue(Settings::VARIABLE_SYMBOL_CODE)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $this->settingsRepository->setValue(Settings::ACCOUNT_NUMBER, $values['accountNumber']);

        $variableSymbolCode = $values['variableSymbolCode'];
        if ($variableSymbolCode != $this->settingsRepository->getValue(Settings::VARIABLE_SYMBOL_CODE)) {
            $this->settingsRepository->setValue(Settings::VARIABLE_SYMBOL_CODE, $variableSymbolCode);
            $this->userRepository->setVariableSymbolCode($variableSymbolCode);
        }
    }
}
