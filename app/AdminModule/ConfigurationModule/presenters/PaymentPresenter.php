<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\BankForm;
use App\AdminModule\ConfigurationModule\Forms\IPaymentFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PaymentForm;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofForm;
use App\AdminModule\ConfigurationModule\Forms\TicketsForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use Nette\Application\UI\Form;
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
     * @var PaymentProofForm
     * @inject
     */
    public $paymentProofFormFactory;

    /**
     * @var BankForm
     * @inject
     */
    public $bankFormFactory;

    /**
     * @var TicketsForm
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
    protected function createComponentPaymentProofForm() : Form
    {
        $form = $this->paymentProofFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @throws Throwable
     */
    protected function createComponentBankForm() : Form
    {
        $form = $this->bankFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentTicketsForm() : Form
    {
        $form = $this->ticketsFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
