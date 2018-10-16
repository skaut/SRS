<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Services\BankService;

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
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;


    /**
     * Zkontroluje splatnost přihlášek.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function actionCheck() : void
    {
        $from = $this->settingsRepository->getDateValue(Settings::BANK_DOWNLOAD_FROM);
        $this->bankService->downloadTransactions($from);
    }
}
