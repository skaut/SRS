<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\PaymentForm;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofForm;
use Nette\Application\UI\Form;


class PaymentPresenter extends ConfigurationBasePresenter
{
    /**
     * @var PaymentForm
     * @inject
     */
    public $paymentFormFactory;

    /**
     * @var PaymentProofForm
     * @inject
     */
    public $paymentProofFormFactory;


    protected function createComponentPaymentForm()
    {
        $form = $this->paymentFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentPaymentProofForm()
    {
        $form = $this->paymentProofFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}