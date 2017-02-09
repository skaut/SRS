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

    protected function createComponentProgramForm($name)
    {
        $form = $this->programFormFactory->create();

        $form->setDefaults([
            'basicBlockDuration' => $this->settingsRepository->getValue('basic_block_duration'),
            'isAllowedAddBlock' => $this->settingsRepository->getValue('is_allowed_add_block'),
            'isAllowedModifySchedule' => $this->settingsRepository->getValue('is_allowed_modify_schedule'),
            'isAllowedRegisterPrograms' => $this->settingsRepository->getValue('is_allowed_register_programs'),
            'isAllowedRegisterProgramsBeforePayment' => $this->settingsRepository->getValue('is_allowed_register_programs_before_payment'),
            'registerProgramsFrom' => $this->settingsRepository->getDateTimeValue('register_programs_from'),
            'registerProgramsTo' => $this->settingsRepository->getDateTimeValue('register_programs_to')
        ]);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->settingsRepository->setValue('basic_block_duration', $values['basicBlockDuration']);
            $this->settingsRepository->setValue('is_allowed_add_block', $values['isAllowedAddBlock']);
            $this->settingsRepository->setValue('is_allowed_modify_schedule', $values['isAllowedModifySchedule']);
            $this->settingsRepository->setValue('is_allowed_register_programs', $values['isAllowedRegisterPrograms']);
            $this->settingsRepository->setValue('is_allowed_register_programs_before_payment', $values['isAllowedRegisterProgramsBeforePayment']);
            $this->settingsRepository->setDateTimeValue('register_programs_from', $values['registerProgramsFrom']);
            $this->settingsRepository->setDateTimeValue('register_programs_to', $values['registerProgramsTo']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}