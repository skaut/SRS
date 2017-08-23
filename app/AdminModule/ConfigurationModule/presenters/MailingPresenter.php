<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\MailingForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingPresenter extends ConfigurationBasePresenter
{
    /**
     * @var MailingForm
     * @inject
     */
    public $mailingFormFactory;

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

        $this->redirect('Mailing:default');
    }

    protected function createComponentMailingForm()
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $this->mailingFormFactory->onEmailChange[] = function () {
            $this->flashMessage('admin.configuration.mailing_email_verification_needed', 'warning');
        };

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
