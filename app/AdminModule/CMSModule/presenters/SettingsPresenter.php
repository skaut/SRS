<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Forms\SettingsForm;
use Nette\Application\UI\Form;

class SettingsPresenter extends CMSBasePresenter
{
    /**
     * @var SettingsForm
     * @inject
     */
    public $settingsFormFactory;

    public function renderDefault() {
        $this->template->logo = $this->settingsRepository->getValue('logo');
    }

    protected function createComponentSettingsForm($name)
    {
        $form = $this->settingsFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.cms.settings_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}