<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\BankFormFactory;
use App\AdminModule\ConfigurationModule\Forms\IPaymentFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PaymentForm;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofFormFactory;
use App\AdminModule\ConfigurationModule\Forms\TicketsFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use stdClass;
use Throwable;

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
     * @var PaymentProofFormFactory
     * @inject
     */
    public $paymentProofFormFactory;

    /**
     * @var BankFormFactory
     * @inject
     */
    public $bankFormFactory;

    /**
     * @var TicketsFormFactory
     * @inject
     */
    public $ticketsFormFactory;


    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDefault() : void
    {
        $bankToken = $this->settingsService->getValue(Settings::BANK_TOKEN);
        if ($bankToken !== null) {
            $this->template->connected = true;
        } else {
            $this->template->connected = false;
        }
    }

    /**
     * Zruší propojení s API banky.
     * @throws SettingsException
     * @throws Throwable
     */
    public function handleDisconnect() : void
    {
        $this->settingsService->setValue(Settings::BANK_TOKEN, null);

        $this->flashMessage('admin.configuration.payment.bank.disconnect_successful', 'success');
        $this->redirect('this');
    }

    protected function createComponentPaymentForm() : PaymentForm
    {
        $control = $this->paymentFormFactory->create();

        $control->onSave[] = function () : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $control;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentPaymentProofForm() : BaseForm
    {
        $form = $this->paymentProofFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @throws Throwable
     */
    protected function createComponentBankForm() : BaseForm
    {
        $form = $this->bankFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentTicketsForm() : BaseForm
    {
        $form = $this->ticketsFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
