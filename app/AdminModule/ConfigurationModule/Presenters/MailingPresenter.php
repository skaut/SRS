<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\MailingFormFactory;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení mailingu.
 */
class MailingPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public MailingFormFactory $mailingFormFactory;

    /**
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $this->template->waiting = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL_VERIFICATION_CODE)) !== null;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentMailingForm(): Form
    {
        $form = $this->mailingFormFactory->create($this->user->getId());

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
