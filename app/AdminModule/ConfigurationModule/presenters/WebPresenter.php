<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\WebForm;
use App\Model\Settings\Settings;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení webové prezentace.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class WebPresenter extends ConfigurationBasePresenter
{
    /**
     * @var WebForm
     * @inject
     */
    public $webFormFactory;


    /**
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function renderDefault()
    {
        $this->template->logo = $this->settingsRepository->getValue(Settings::LOGO);
    }

    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    protected function createComponentSettingsForm()
    {
        $form = $this->webFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
