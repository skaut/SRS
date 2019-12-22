<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use App\Model\Settings\SettingsException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení semináře.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SeminarPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SeminarFormFactory
     * @inject
     */
    public $seminarFormFactory;

    /**
     * @throws SettingsException
     * @throws Throwable
     */
    protected function createComponentSeminarForm() : Form
    {
        $form = $this->seminarFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
