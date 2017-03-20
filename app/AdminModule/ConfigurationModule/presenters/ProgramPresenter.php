<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Forms\ProgramForm;
use Nette\Application\UI\Form;


class ProgramPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ProgramForm
     * @inject
     */
    public $programFormFactory;


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