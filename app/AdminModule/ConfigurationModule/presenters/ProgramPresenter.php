<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\ProgramFormFactory;
use Nette\Application\UI\Form;

class ProgramPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ProgramFormFactory
     * @inject
     */
    public $programFormFactory;

    protected function createComponentProgramForm($name)
    {
        $form = $this->programFormFactory->create();

        $form->setDefaults([
            'basicBlockDuration' => $this->settingsRepository->getValue('basic_block_duration'),
            'isAllowedAddBlock' => $this->settingsRepository->getValue('is_allowed_add_block'),
            'isAllowedModifySchedule' => $this->settingsRepository->getValue('is_allowed_modify_schedule'),
            'isAllowedLogInPrograms' => $this->settingsRepository->getValue('is_allowed_log_in_programs'),
            'isAllowedLogInProgramsBeforePayment' => $this->settingsRepository->getValue('is_allowed_log_in_programs_before_payment'),
            'logInProgramsFrom' => $this->settingsRepository->getDateTimeValue('log_in_programs_from'),
            'logInProgramsTo' => $this->settingsRepository->getDateTimeValue('log_in_programs_to')
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('basic_block_duration', $values['basicBlockDuration']);
            $this->settingsRepository->setValue('is_allowed_add_block', $values['isAllowedAddBlock']);
            $this->settingsRepository->setValue('is_allowed_modify_schedule', $values['isAllowedModifySchedule']);
            $this->settingsRepository->setValue('is_allowed_log_in_programs', $values['isAllowedLogInPrograms']);
            $this->settingsRepository->setValue('is_allowed_log_in_programs_before_payment', $values['isAllowedLogInProgramsBeforePayment']);
            $this->settingsRepository->setDateTimeValue('log_in_programs_from', $values['logInProgramsFrom']);
            $this->settingsRepository->setDateTimeValue('log_in_programs_to', $values['logInProgramsTo']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}