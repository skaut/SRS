<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\MailingFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\SettingsService;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení mailingu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailingPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public MailingFormFactory $mailingFormFactory;

    /** @inject */
    public SettingsService $settingsService;

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDefault() : void
    {
        $this->template->waiting = $this->settingsService->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE) !== null;
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentMailingForm() : Form
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
