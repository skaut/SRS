<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\MailingForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
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
     * @throws SettingsException
     * @throws \Throwable
     */
    public function renderDefault() : void
    {
        $this->template->waiting = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE) !== null;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    protected function createComponentMailingForm() : Form
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
