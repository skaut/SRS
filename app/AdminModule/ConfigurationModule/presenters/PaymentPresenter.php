<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\IPaymentFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofForm;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení platby a dokladů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentPresenter extends ConfigurationBasePresenter
{
    /**
     * @var IPaymentFormFactory
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
        $control = $this->paymentFormFactory->create();

        $control->onSave[] = function () {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $control;
    }

    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    protected function createComponentPaymentProofForm()
    {
        $form = $this->paymentProofFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
