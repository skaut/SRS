<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Nette;
use Nette\Application\UI\Form;

class SeminarForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var SettingsRepository */
    private $settingsRepository;

    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('seminarName', 'admin.configuration.seminar_name')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_name_empty');

        $seminarFromDate = $form->addDatePicker('seminarFromDate', 'admin.configuration.seminar_from_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_from_date_empty');

        $seminarToDate = $form->addDatePicker('seminarToDate', 'admin.configuration.seminar_to_date')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_to_date_empty');

        $editRegistrationTo = $form->addDatePicker('editRegistrationTo', 'admin.configuration.edit_registration_to')
            ->addRule(Form::FILLED, 'admin.configuration.edit_registration_to_empty');

        $seminarFromDate->addRule([$this, 'validateSeminarFromDate'], 'admin.configuration.seminar_from_date_after_to', [$seminarFromDate, $seminarToDate]);
        $seminarToDate->addRule([$this, 'validateSeminarToDate'], 'admin.configuration.seminar_to_date_before_from', [$seminarToDate, $seminarFromDate]);
        $editRegistrationTo->addRule([$this, 'validateEditRegistrationTo'], 'admin.configuration.edit_registration_to_after_from', [$editRegistrationTo, $seminarFromDate]);

        $form->addText('seminarEmail', 'admin.configuration.seminar_email')
            ->addRule(Form::FILLED, 'admin.configuration.seminar_email_empty')
            ->addRule(Form::EMAIL, 'admin.configuration.seminar_email_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarName' => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            'seminarFromDate' => $this->settingsRepository->getDateValue(Settings::SEMINAR_FROM_DATE),
            'seminarToDate' => $this->settingsRepository->getDateValue(Settings::SEMINAR_TO_DATE),
            'editRegistrationTo' => $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO),
            'seminarEmail' => $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        $this->settingsRepository->setValue(Settings::SEMINAR_NAME, $values['seminarName']);
        $this->settingsRepository->setDateValue(Settings::SEMINAR_FROM_DATE, $values['seminarFromDate']);
        $this->settingsRepository->setDateValue(Settings::SEMINAR_TO_DATE, $values['seminarToDate']);
        $this->settingsRepository->setDateValue(Settings::EDIT_REGISTRATION_TO, $values['editRegistrationTo']);
        $this->settingsRepository->setValue(Settings::SEMINAR_EMAIL, $values['seminarEmail']);
    }

    public function validateSeminarFromDate($field, $args)
    {
        return $args[0] <= $args[1];
    }

    public function validateSeminarToDate($field, $args)
    {
        return $args[0] >= $args[1];
    }

    public function validateEditRegistrationTo($field, $args)
    {
        return $args[0] < $args[1];
    }
}
