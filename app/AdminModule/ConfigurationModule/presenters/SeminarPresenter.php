<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\SettingsException;
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
    protected function createComponentSeminarForm() : BaseForm
    {
        $form = $this->seminarFormFactory->create();

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
