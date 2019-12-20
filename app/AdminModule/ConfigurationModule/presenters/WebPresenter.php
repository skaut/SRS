<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\WebFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
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
    /**
     * @var WebFormFactory
     * @inject
     */
    public $webFormFactory;

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
    protected function createComponentSettingsForm() : BaseForm
    {
        $form = $this->webFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
