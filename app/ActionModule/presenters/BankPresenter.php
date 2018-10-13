<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Settings\SettingsException;
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
     * Zkontroluje splatnost přihlášek.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function actionCheck() : void
    {
        $this->bankService->downloadLastTransactions();
    }
}
