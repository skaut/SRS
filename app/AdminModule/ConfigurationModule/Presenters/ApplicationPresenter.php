<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\CustomInputsGridControl;
use App\AdminModule\ConfigurationModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\ApplicationFormFactory;
use App\AdminModule\ConfigurationModule\Forms\CustomInputFormFactory;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public CustomInputRepository $customInputRepository;

    /** @inject */
    public ICustomInputsGridControlFactory $customInputsGridControlFactory;

    /** @inject */
    public ApplicationFormFactory $applicationFormFactory;

    /** @inject */
    public CustomInputFormFactory $customInputFormFactory;

    public function renderEdit(int $id): void
    {
        $this->template->customInput = $this->customInputRepository->findById($id);
    }

    protected function createComponentCustomInputsGrid(): CustomInputsGridControl
    {
        return $this->customInputsGridControlFactory->create();
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentApplicationForm(): Form
    {
        $form = $this->applicationFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentCustomInputForm(): Form
    {
        $form = $this->customInputFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() !== $form['cancel']) {
                $this->flashMessage('admin.configuration.custom_inputs_saved', 'success');
            }

            $this->redirect('Application:default');
        };

        return $form;
    }
}
