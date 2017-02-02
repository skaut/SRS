<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use Nette\Application\UI\Form;

class SeminarPresenter extends ConfigurationBasePresenter
{
    /**
     * @var SeminarFormFactory
     * @inject
     */
    public $seminarFormFactory;

    protected function createComponentSeminarForm($name)
    {
        $form = $this->seminarFormFactory->create();

        $form->setDefaults([
            'seminarName' => $this->settingsRepository->getValue('seminar_name'),
            'seminarFromDate' => $this->settingsRepository->getDateValue('seminar_from_date'),
            'seminarToDate' => $this->settingsRepository->getDateValue('seminar_to_date'),
            'editRegistrationTo' => $this->settingsRepository->getDateValue('edit_registration_to'),
            'seminarEmail' => $this->settingsRepository->getValue('seminar_email')
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('seminar_name', $values['seminarName']);
            $this->settingsRepository->setDateValue('seminar_from_date', $values['seminarFromDate']);
            $this->settingsRepository->setDateValue('seminar_to_date', $values['seminarToDate']);
            $this->settingsRepository->setDateValue('edit_registration_to', $values['editRegistrationTo']);
            $this->settingsRepository->setValue('seminar_email', $values['seminarEmail']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}