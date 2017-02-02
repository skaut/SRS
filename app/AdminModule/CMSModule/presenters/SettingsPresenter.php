<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Forms\SettingsFormFactory;
use App\Services\FilesService;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

class SettingsPresenter extends CMSBasePresenter
{
    /**
     * @var SettingsFormFactory
     * @inject
     */
    public $settingsFormFactory;

    /**
     * @var FilesService
     * @inject
     */
    public $filesService;

    public function renderDefault() {
        $this->template->logo = $this->settingsRepository->getValue('logo');
    }

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

            $logo = $values['logo'];
            if ($logo->name) {
                $this->filesService->delete('/logo/' . $this->settingsRepository->getValue('logo'));

                $logoName = Strings::webalize($logo->name, '.');
                $this->filesService->save($logo, '/logo/' . $logoName);
                $this->filesService->resizeImage('/logo/' . $logoName, null, 100);

                $this->settingsRepository->setValue('logo', $logoName);
            }

            $this->settingsRepository->setValue('footer', $values['footer']);
            $this->settingsRepository->setValue('redirect_after_login', $values['redirectAfterLogin']);
            $this->settingsRepository->setValue('display_users_roles', $values['displayUsersRoles']);

            $this->flashMessage('admin.cms.settings_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}