<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Forms\SettingsFormFactory;

class SettingsPresenter extends CMSBasePresenter
{
    /**
     * @var SettingsFormFactory
     * @inject
     */
    public $settingsFormFactory;

    protected function createComponentSettingsForm($name)
    {
        $form = $this->settingsFormFactory->create();

        $form->setDefaults([
            'footer' => $this->settingsRepository->getValue('footer'),
            'redirectAfterLogin' => $this->settingsRepository->getValue('redirect_after_login'),
            'displayUsersRoles' => $this->settingsRepository->getValue('display_users_roles')
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('footer', $values['footer']);
            $this->settingsRepository->setValue('redirect_after_login', $values['redirectAfterLogin']);
            $this->settingsRepository->setValue('display_users_roles', $values['displayUsersRoles']);

            $this->flashMessage('admin.cms.settings_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}