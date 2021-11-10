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
use Endroid\QrCode\QrCode;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Tracy\Debugger;

/**
 * Presenter obsluhující nastavení vstupenek.
 */
class TicketsPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public CommandBus $commandBus;

    /** @inject */
    public TicketsFormFactory $ticketsFormFactory;

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $apiToken = $this->queryBus->handle(new SettingStringValueQuery(Settings::TICKETS_API_TOKEN));

        $this->template->apiToken = $apiToken;

        $connectionInfo = [];
        $connectionInfo['apiUrl'] = $this->getHttpRequest()->getUrl()->getBasePath() . "/api/tickets";
        $connectionInfo['apiToken'] = $apiToken;

        $connectionInfoJson = json_encode($connectionInfo);

        $qrCode = new QrCode();
        $qrCode
            ->setText((string) $connectionInfoJson)
            ->setSize(300)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrImg = $qrCode->writeDataUri();
        $this->template->qrkod = $qrImg;
        // Debugger::dump($qrImg);
    }

    /**
     * Vygeneruje token pro aplikaci.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function handleGenerateToken(): void
    {
        $apiToken = bin2hex(random_bytes(40));
        $this->commandBus->handle(new SetSettingStringValue(Settings::TICKETS_API_TOKEN, $apiToken));
        $this->flashMessage('admin.configuration.payment.bank.disconnect_successful', 'success');
        $this->redirect('this');
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentTicketsForm(): Form
    {
        $form = $this->ticketsFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
