<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Services\ISettingsService;
use Nette\Application\AbortException;
use Throwable;

/**
 * Presenter obsluhující potvrzení změny e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingPresenter extends ActionBasePresenter
{
    /** @inject */
    public ISettingsService $settingsService;

    /**
     * Ověří e-mail semináře.
     *
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function actionVerify(string $code): void
    {
        if ($code === $this->settingsService->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE)) {
            $newEmail = $this->settingsService->getValue(Settings::SEMINAR_EMAIL_UNVERIFIED);
            $this->settingsService->setValue(Settings::SEMINAR_EMAIL, $newEmail);

            $this->settingsService->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, null);
            $this->settingsService->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, null);

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
