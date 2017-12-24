<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení semináře.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SeminarForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * SeminarForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository,
                                SubeventRepository $subeventRepository)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     * @throws \App\Model\Settings\SettingsException
     */
    public function create()
    {
        $form = $this->baseFormFactory->create();

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

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'seminarName' => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            'seminarFromDate' => $this->settingsRepository->getDateValue(Settings::SEMINAR_FROM_DATE),
            'seminarToDate' => $this->settingsRepository->getDateValue(Settings::SEMINAR_TO_DATE),
            'editRegistrationTo' => $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \App\Model\Settings\SettingsException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->settingsRepository->setValue(Settings::SEMINAR_NAME, $values['seminarName']);
        $implicitSubevent = $this->subeventRepository->findImplicit();
        $implicitSubevent->setName($values['seminarName']);
        $this->subeventRepository->save($implicitSubevent);

        $this->settingsRepository->setDateValue(Settings::SEMINAR_FROM_DATE, $values['seminarFromDate']);
        $this->settingsRepository->setDateValue(Settings::SEMINAR_TO_DATE, $values['seminarToDate']);
        $this->settingsRepository->setDateValue(Settings::EDIT_REGISTRATION_TO, $values['editRegistrationTo']);
    }

    /**
     * Ověří, že datum začátku semináře je dříve než konce.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSeminarFromDate($field, $args)
    {
        return $args[0] <= $args[1];
    }

    /**
     * Ověří, že datum konce semináře je později než začátku.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSeminarToDate($field, $args)
    {
        return $args[0] >= $args[1];
    }

    /**
     * Ověří, že datum uzavření registrace je dříve než začátek semináře.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateEditRegistrationTo($field, $args)
    {
        return $args[0] < $args[1];
    }
}
