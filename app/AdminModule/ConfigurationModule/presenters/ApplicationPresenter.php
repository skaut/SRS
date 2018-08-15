<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\CustomInputsGridControl;
use App\AdminModule\ConfigurationModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\ApplicationForm;
use App\AdminModule\ConfigurationModule\Forms\CustomInputForm;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsException;
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
     * @var CustomInputForm
     * @inject
     */
    public $customInputFormFactory;


    public function renderEdit($id) : void
    {
        $this->template->customInput = $this->customInputRepository->findById($id);
    }

    protected function createComponentCustomInputsGrid() : CustomInputsGridControl
    {
        return $this->customInputsGridControlFactory->create();
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    protected function createComponentApplicationForm() : Form
    {
        $form = $this->applicationFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentCustomInputForm() : Form
    {
        $form = $this->customInputFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, array $values) : void {
            if (! $form['cancel']->isSubmittedBy()) {
                $this->flashMessage('admin.configuration.custom_inputs_saved', 'success');
            }

            $this->redirect('Application:default');
        };

        return $form;
    }
}
