<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\TicketsFormFactory;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use Endroid\QrCode\QrCode;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

use function bin2hex;
use function json_encode;
use function random_bytes;

use const JSON_THROW_ON_ERROR;

/**
 * Presenter obsluhující nastavení vstupenek.
 */
class TicketsPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public CommandBus $commandBus;

    #[Inject]
    public TicketsFormFactory $ticketsFormFactory;

    /**
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $apiToken                 = $this->queryBus->handle(new SettingStringValueQuery(Settings::TICKETS_API_TOKEN));
        $this->template->apiToken = $apiToken;

        $connectionInfo             = [];
        $connectionInfo['apiUrl']   = $this->getHttpRequest()->getUrl()->getBaseUrl() . 'api/';
        $connectionInfo['apiToken'] = $apiToken;
        $this->template->qr         = $this->generateQr(json_encode($connectionInfo, JSON_THROW_ON_ERROR));
    }

    /**
     * Vygeneruje token pro aplikaci.
     *
     * @throws Throwable
     */
    public function handleGenerateToken(): void
    {
        $apiToken = bin2hex(random_bytes(40));
        $this->commandBus->handle(new SetSettingStringValue(Settings::TICKETS_API_TOKEN, $apiToken));
        $this->flashMessage('admin.configuration.tickets.scanner.generate_token_success', 'success');
        $this->redirect('this');
    }

    /**
     * Odstraní token pro aplikaci.
     *
     * @throws Throwable
     */
    public function handleDeleteToken(): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::TICKETS_API_TOKEN, null));
        $this->flashMessage('admin.configuration.tickets.scanner.delete_token_success', 'success');
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

    private function generateQr(string $text): string
    {
        $qrCode = new QrCode();
        $qrCode
            ->setText($text)
            ->setSize(200)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

        return $qrCode->get();
    }
}
