<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\Forms\PaymentConfigurationFormFactory;
use App\AdminModule\Forms\PaymentProofConfigurationFormFactory;
use App\AdminModule\Forms\ProgramConfigurationFormFactory;
use App\AdminModule\Forms\SeminarConfigurationFormFactory;
use App\AdminModule\Forms\SkautIsActionConfigurationFormFactory;
use App\AdminModule\Forms\SystemConfigurationFormFactory;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use Nette\Application\UI\Form;
use Skautis\Skautis;

class ConfigurationPresenter extends AdminBasePresenter
{
    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var CustomInputRepository
     * @inject
     */
    public $customInputRepository;

    /**
     * @var SeminarConfigurationFormFactory
     * @inject
     */
    public $seminarConfigurationFormFactory;

    /**
     * @var ProgramConfigurationFormFactory
     * @inject
     */
    public $programConfigurationFormFactory;

    /**
     * @var PaymentConfigurationFormFactory
     * @inject
     */
    public $paymentConfigurationFormFactory;

    /**
     * @var PaymentProofConfigurationFormFactory
     * @inject
     */
    public $paymentProofConfigurationFormFactory;

    /**
     * @var SkautIsActionConfigurationFormFactory
     * @inject
     */
    public $skautIsActionConfigurationFormFactory;

    /**
     * @var SystemConfigurationFormFactory
     * @inject
     */
    public $systemConfigurationFormFactory;

    /**
     * @var ICustomInputsGridControlFactory
     * @inject
     */
    public $customInputsGridControlFactory;

    /**
     * @var Skautis
     * @inject
     */
    public $skautIS;

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }

    public function renderSkautIs()
    {
        $this->template->connected = true;
    }

    public function createComponentSeminarConfigurationForm($name)
    {
        $form = $this->seminarConfigurationFormFactory->create();

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

    public function createComponentProgramConfigurationForm($name)
    {
        $form = $this->programConfigurationFormFactory->create();

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

    public function createComponentPaymentConfigurationForm($name)
    {
        $form = $this->paymentConfigurationFormFactory->create();

        $form->setDefaults([
            'accountNumber' => $this->settingsRepository->getValue('account_number'),
            'variableSymbolCode' => $this->settingsRepository->getValue('variable_symbol_code')
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('account_number', $values['accountNumber']);
            $this->settingsRepository->setValue('variable_symbol_code', $values['variableSymbolCode']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    public function createComponentPaymentProofConfigurationForm($name)
    {
        $form = $this->paymentProofConfigurationFormFactory->create();

        $form->setDefaults([
            'company' => $this->settingsRepository->getValue('company'),
            'ico' => $this->settingsRepository->getValue('ico'),
            'accountant' => $this->settingsRepository->getValue('accountant'),
            'printLocation' => $this->settingsRepository->getValue('print_location')
        ]);

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('company', $values['company']);
            $this->settingsRepository->setValue('ico', $values['ico']);
            $this->settingsRepository->setValue('accountant', $values['accountant']);
            $this->settingsRepository->setValue('print_location', $values['printLocation']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    public function createComponentSkautIsActionConfigurationForm($name)
    {
        $form = $this->skautIsActionConfigurationFormFactory->create();

        $skautIsAction = $this->settingsRepository->getValue('skautis_action');
        if ($skautIsAction) {
            $form->setDefaults([
                'skautisAction' => $skautIsAction
            ]);
        }

        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues();

            $this->settingsRepository->setValue('skautis_action', $values['skautisAction']);

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    public function createComponentSystemConfigurationForm($name)
    {
        $form = $this->systemConfigurationFormFactory->create();

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

            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    public function createComponentCustomInputsGrid($name)
    {
        return $this->customInputsGridControlFactory->create();
    }
}