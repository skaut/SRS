<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\SeminarForm;
use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use Nette\Application\UI\Form;

class SeminarPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SeminarForm
     * @inject
     */
    public $seminarFormFactory;

    protected function createComponentSeminarForm($name)
    {
        $form = $this->seminarFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}