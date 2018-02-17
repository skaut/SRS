<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\ProgramForm;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení programu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ProgramForm
     * @inject
     */
    public $programFormFactory;


    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    protected function createComponentProgramForm()
    {
        $form = $this->programFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
