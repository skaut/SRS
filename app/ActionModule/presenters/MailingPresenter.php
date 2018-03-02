<?php

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;


/**
 * Presenter obsluhující potvrzení změny e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingPresenter extends ActionBasePresenter
{
    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;


    /**
     * Ověří e-mail semináře.
     * @param $code
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     */
    public function actionVerify($code)
    {
        if ($code == $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE)) {
            $newEmail = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_UNVERIFIED);
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL, $newEmail);

            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, NULL);
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, NULL);

            $this->flashMessage('admin.configuration.mailing_email_verification_successful', 'success');
        } else {
            $this->flashMessage('admin.configuration.mailing_email_verification_error', 'danger');
        }

        if ($this->user->isAllowed(Resource::CONFIGURATION, Permission::MANAGE)) {
            $this->redirect(':Admin:Configuration:Mailing:default');
        } else {
            $this->redirect(':Web:Page:default');
        }
    }
}
