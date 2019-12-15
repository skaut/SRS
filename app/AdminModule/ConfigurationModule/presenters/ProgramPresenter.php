<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\ProgramFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\SettingsException;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení programu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ProgramFormFactory
     * @inject
     */
    public $programFormFactory;


    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentProgramForm() : BaseForm
    {
        $form = $this->programFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
