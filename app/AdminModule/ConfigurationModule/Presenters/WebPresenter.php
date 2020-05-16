<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\WebFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení webové prezentace.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class WebPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public WebFormFactory $webFormFactory;

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    public function renderDefault() : void
    {
        $this->template->logo = $this->settingsService->getValue(Settings::LOGO);
    }

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentSettingsForm() : Form
    {
        $form = $this->webFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
