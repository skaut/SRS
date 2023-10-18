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

    /**
     * Ověří e-mail semináře.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionVerify(string $code): void
    {
        if ($code === $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL_VERIFICATION_CODE))) {
            $newEmail = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL_UNVERIFIED));
            $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_EMAIL, $newEmail));

            $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_EMAIL_UNVERIFIED, null));
            $this->commandBus->handle(new SetSettingStringValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, null));

            $this->flashMessage('admin.configuration.mailing_email_verification_successful', 'success');
        } else {
            $this->flashMessage('admin.configuration.mailing_email_verification_error', 'danger');
        }

        if ($this->user->isAllowed(SrsResource::CONFIGURATION, Permission::MANAGE)) {
            $this->redirect(':Admin:Configuration:Mailing:default');
        } else {
            $this->redirect(':Web:Page:default');
        }
    }
}
