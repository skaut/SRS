<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\CustomInputsGridControl;
use App\AdminModule\ConfigurationModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\ApplicationForm;
use App\Model\Settings\CustomInput\CustomInputRepository;
use Nette\Application\UI\Form;


/**
 * Presenter obsluhující nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationPresenter extends ConfigurationBasePresenter
{
    /**
     * @var CustomInputRepository
     * @inject
     */
    public $customInputRepository;

    /**
     * @var ICustomInputsGridControlFactory
     * @inject
     */
    public $customInputsGridControlFactory;

    /**
     * @var ApplicationForm
     * @inject
     */
    public $applicationFormFactory;


    /**
     * @return CustomInputsGridControl
     */
    protected function createComponentCustomInputsGrid()
    {
        return $this->customInputsGridControlFactory->create();
    }

    /**
     * @return Form
     */
    protected function createComponentApplicationForm()
    {
        $form = $this->applicationFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}