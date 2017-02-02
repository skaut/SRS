<?php

namespace App\AdminModule\CMSModule\Presenters;


class SettingsPresenter extends CMSBasePresenter
{
//    protected function createComponentSystemConfigurationForm($name)
//    {
//        $form = $this->systemConfigurationFormFactory->create();
//
//        $form->setDefaults([
//            'footer' => $this->settingsRepository->getValue('footer'),
//            'redirectAfterLogin' => $this->settingsRepository->getValue('redirect_after_login'),
//            'displayUsersRoles' => $this->settingsRepository->getValue('display_users_roles')
//        ]);
//
//        $form->onSuccess[] = function (Form $form) {
//            $values = $form->getValues();
//
//            $this->settingsRepository->setValue('footer', $values['footer']);
//            $this->settingsRepository->setValue('redirect_after_login', $values['redirectAfterLogin']);
//            $this->settingsRepository->setValue('display_users_roles', $values['displayUsersRoles']);
//
//            $this->flashMessage('admin.configuration.configuration_saved', 'success');
//
//            $this->redirect('this');
//        };
//
//        return $form;
//    }
}