<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\ProgramForm;
use App\Model\Settings\SettingsException;
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
     * @throws SettingsException
     * @throws \Throwable
     */
    protected function createComponentProgramForm() : Form
    {
        $form = $this->programFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
