<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\PaymentFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofFormFactory;
use Nette\Application\UI\Form;

class PaymentPresenter extends ConfigurationBasePresenter
{
    /**
     * @var PaymentFormFactory
     * @inject
     */
    public $paymentFormFactory;

    /**
     * @var PaymentProofFormFactory
     * @inject
     */
    public $paymentProofFormFactory;

    protected function createComponentPaymentForm($name)
    {
        $form = $this->paymentFormFactory->create();

        $form->setDefaults([
            'accountNumber' => $this->settingsRepository->getValue('account_number'),
            'variableSymbolCode' => $this->settingsRepository->getValue('variable_symbol_code')
        ]);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->settingsRepository->setValue('account_number', $values['accountNumber']);
            $this->settingsRepository->setValue('variable_symbol_code', $values['variableSymbolCode']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentPaymentProofForm($name)
    {
        $form = $this->paymentProofFormFactory->create();

        $form->setDefaults([
            'company' => $this->settingsRepository->getValue('company'),
            'ico' => $this->settingsRepository->getValue('ico'),
            'accountant' => $this->settingsRepository->getValue('accountant'),
            'printLocation' => $this->settingsRepository->getValue('print_location')
        ]);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->settingsRepository->setValue('company', $values['company']);
            $this->settingsRepository->setValue('ico', $values['ico']);
            $this->settingsRepository->setValue('accountant', $values['accountant']);
            $this->settingsRepository->setValue('print_location', $values['printLocation']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}