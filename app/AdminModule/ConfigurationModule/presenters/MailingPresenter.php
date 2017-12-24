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


    public function renderDefault()
    {
        $this->template->waiting = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE) !== NULL;
    }

    protected function createComponentMailingForm()
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
