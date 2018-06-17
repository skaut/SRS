<?php
declare(strict_types=1);

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
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function renderDefault()
    {
        $this->template->waiting = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL_VERIFICATION_CODE) !== NULL;
    }

    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    protected function createComponentMailingForm()
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
