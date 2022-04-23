<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Settings;
use App\Services\BankService;
use App\Services\QueryBus;
use Nette\Application\Responses\TextResponse;
use Nette\DI\Attributes\Inject;
use Throwable;

/**
 * Presenter obsluhující načítání plateb z API banky.
 */
class BankPresenter extends ActionBasePresenter
{
    #[Inject]
    public QueryBus $queryBus;

    #[Inject]
    public BankService $bankService;

    /**
     * Zkontroluje splatnost přihlášek.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function actionCheck(): void
    {
        $from = $this->queryBus->handle(new SettingDateValueQuery(Settings::BANK_DOWNLOAD_FROM));
        $this->bankService->downloadTransactions($from);

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }
}
