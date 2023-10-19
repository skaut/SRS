<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Mailing\Commands\SendMails;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette\Application\AbortException;
use Nette\Application\Responses\TextResponse;
use Nette\DI\Attributes\Inject;
use Throwable;

/**
 * Presenter obsluhující potvrzení změny e-mailu.
 */
class MailPresenter extends ApiBasePresenter
{
    #[Inject]
    public CommandBus $commandBus;

    #[Inject]
    public QueryBus $queryBus;

    /**
     * Odešle e-maily z fronty.
     */
    public function actionSend(): void
    {
        $this->commandBus->handle(new SendMails());

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }
}
