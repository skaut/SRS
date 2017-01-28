<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\Forms\PaymentConfigurationFormFactory;
use App\AdminModule\Forms\PaymentProofConfigurationFormFactory;
use App\AdminModule\Forms\ProgramConfigurationFormFactory;
use App\AdminModule\Forms\SeminarConfigurationFormFactory;
use App\AdminModule\Forms\SystemConfigurationFormFactory;
use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use Nette\Application\UI\Form;

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
     * @var SystemConfigurationFormFactory
     * @inject
     */
    public $systemConfigurationFormFactory;

    /**
     * @var ICustomInputsGridControlFactory
     * @inject
     */
    public $customInputsGridControlFactory;

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
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
            $this->settingsRepository->setDateValue('edit_registration_to', $values['editRegistrationTo']); //TODO validace rozsahu datumu
            $this->settingsRepository->setValue('seminar_email', $values['seminarEmail']);

            $this->flashMessage('Konfigurace úspěšně uložena.', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    public function createComponentProgramConfigurationForm($name)
    {
        $form = $this->programConfigurationFormFactory->create();

        return $form;
    }

    public function createComponentPaymentConfigurationForm($name)
    {
        $form = $this->paymentConfigurationFormFactory->create();

        return $form;
    }

    public function createComponentPaymentProofConfigurationForm($name)
    {
        $form = $this->paymentProofConfigurationFormFactory->create();

        return $form;
    }

    public function createComponentSystemConfigurationForm($name)
    {
        $form = $this->systemConfigurationFormFactory->create();

        return $form;
    }

    public function createComponentCustomInputsGrid($name)
    {
        return $this->customInputsGridControlFactory->create();
    }
}