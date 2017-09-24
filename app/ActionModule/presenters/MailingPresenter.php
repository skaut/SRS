<?php

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use Nette\Application\Responses\TextResponse;


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
     */
    public function actionVerify($code)
    {
        if ($code == $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE)) {
            $newEmail = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_UNVERIFIED);
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL, $newEmail);

            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, NULL);
            $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, NULL);

            $this->flashMessage('admin.configuration.mailing_email_verification_success', 'success');
        }
        else {
            $this->flashMessage('admin.configuration.mailing_email_verification_error', 'danger');
        }

        if ($this->user->isAllowed(Resource::CONFIGURATION, Permission::MANAGE)) {
            $this->redirect(':Admin:Configuration:Mailing:default');
        }
        else {
            $this->redirect(':Web:Page:default');
        }
    }
}
