<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\BankFormFactory;
use App\AdminModule\ConfigurationModule\Forms\IPaymentFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PaymentForm;
use App\AdminModule\ConfigurationModule\Forms\PaymentProofFormFactory;
use App\AdminModule\ConfigurationModule\Forms\TicketsFormFactory;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení platby a dokladů
 */
class PaymentPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public CommandBus $commandBus;

    #[Inject]
    public IPaymentFormFactory $paymentFormFactory;

    #[Inject]
    public PaymentProofFormFactory $paymentProofFormFactory;

    #[Inject]
    public BankFormFactory $bankFormFactory;

    #[Inject]
    public TicketsFormFactory $ticketsFormFactory;

    /**
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $bankToken = $this->queryBus->handle(new SettingStringValueQuery(Settings::BANK_TOKEN));
        if ($bankToken !== null) {
            $this->template->connected = true;
        } else {
            $this->template->connected = false;
        }
    }

    /**
     * Zruší propojení s API banky
     *
     * @throws Throwable
     */
    public function handleDisconnect(): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::BANK_TOKEN, null));

        $this->flashMessage('admin.configuration.payment.bank.disconnect_successful', 'success');
        $this->redirect('this');
    }

    protected function createComponentPaymentForm(): PaymentForm
    {
        $control = $this->paymentFormFactory->create();

        $control->onSave[] = function (): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $control;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentPaymentProofForm(): Form
    {
        $form = $this->paymentProofFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    /**
     * @throws Throwable
     */
    protected function createComponentBankForm(): Form
    {
        $form = $this->bankFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
