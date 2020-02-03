<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\BankService;
use App\Services\SettingsService;
use Nette\Application\Responses\TextResponse;
use Throwable;

/**
 * Presenter obsluhující načítání plateb z API banky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BankPresenter extends ActionBasePresenter
{
    /**
     * @var BankService
     * @inject
     */
    public $bankService;

    /**
     * @var SettingsService
     * @inject
     */
    public $settingsService;

    /**
     * Zkontroluje splatnost přihlášek.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function actionCheck() : void
    {
        $from = $this->settingsService->getDateValue(Settings::BANK_DOWNLOAD_FROM);
        $this->bankService->downloadTransactions($from);

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }
}
