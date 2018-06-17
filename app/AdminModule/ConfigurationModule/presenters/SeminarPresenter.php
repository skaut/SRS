<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\SeminarForm;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení semináře.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SeminarPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SeminarForm
     * @inject
     */
    public $seminarFormFactory;


    /**
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    protected function createComponentSeminarForm()
    {
        $form = $this->seminarFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}
