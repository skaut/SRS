<?php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use App\Model\Mailing\Commands\SendMails;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette\Application\Responses\TextResponse;
use Nette\DI\Attributes\Inject;

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
