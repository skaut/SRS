<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Nette\Application\AbortException;

/**
 * Presenter obsluhující potvrzení změny e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class MailingPresenter extends ActionBasePresenter
{
    /**
     * @var SettingsFacade
     * @inject
     */
    public $settingsFacade;


    /**
     * Ověří e-mail semináře.
     * @throws SettingsException
     * @throws AbortException
     * @throws \Throwable
     */
    public function actionVerify(string $code) : void
    {
        if ($code === $this->settingsFacade->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE)) {
            $newEmail = $this->settingsFacade->getValue(Settings::SEMINAR_EMAIL_UNVERIFIED);
            $this->settingsFacade->setValue(Settings::SEMINAR_EMAIL, $newEmail);

            $this->settingsFacade->setValue(Settings::SEMINAR_EMAIL_UNVERIFIED, null);
            $this->settingsFacade->setValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE, null);

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
