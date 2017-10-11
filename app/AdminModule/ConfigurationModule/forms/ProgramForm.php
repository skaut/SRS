<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Enums\RegisterProgramsType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro nastavení programu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseForm;

    /** @var  SettingsRepository */
    private $settingsRepository;

    /** @var Translator */
    private $translator;


    /**
     * ProgramForm constructor.
     * @param BaseForm $baseForm
     * @param SettingsRepository $settingsRepository
     * @param Translator $translator
     */
    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository, Translator $translator)
    {
        $this->baseForm = $baseForm;
        $this->settingsRepository = $settingsRepository;
        $this->translator = $translator;
    }

    /**
     * Vytvoří formulář.
     * @return Form
     */
    public function create()
    {
        $form = $this->baseForm->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $registerProgramsTypeSelect = $form->addSelect('registerProgramsType', 'admin.configuration.register_programs_type',
            $this->prepareRegisterProgramsTypeOptions());
        $registerProgramsTypeSelect
            ->addCondition($form::EQUAL, RegisterProgramsType::ALLOWED_FROM_TO)
            ->toggle('register-programs-from')
            ->toggle('register-programs-to');

        $registerProgramsFrom = $form->addDateTimePicker('registerProgramsFrom', 'admin.configuration.register_programs_from')
            ->setOption('id', 'register-programs-from');

        $registerProgramsTo = $form->addDateTimePicker('registerProgramsTo', 'admin.configuration.register_programs_to')
            ->setOption('id', 'register-programs-to');

        $form->addCheckbox('isAllowedRegisterProgramsBeforePayment', 'admin.configuration.is_allowed_register_programs_before_payment');

        $registerProgramsFrom
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRegisterProgramsFrom'], 'admin.configuration.register_programs_from_after_to', [$registerProgramsFrom, $registerProgramsTo]);

        $registerProgramsTo
            ->addCondition(Form::FILLED)
            ->addRule([$this, 'validateRegisterProgramsTo'], 'admin.configuration.register_programs_to_before_from', [$registerProgramsTo, $registerProgramsFrom]);

        $form->addCheckbox('isAllowedAddBlock', 'admin.configuration.is_allowed_add_block');
        $form->addCheckbox('isAllowedModifySchedule', 'admin.configuration.is_allowed_modify_schedule');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'isAllowedAddBlock' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK),
            'isAllowedModifySchedule' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE),
            'registerProgramsType' => $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE),
            'registerProgramsFrom' => $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM),
            'registerProgramsTo' => $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO),
            'isAllowedRegisterProgramsBeforePayment' => $this->settingsRepository->getValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_ADD_BLOCK, $values['isAllowedAddBlock']);
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE, $values['isAllowedModifySchedule']);
        $this->settingsRepository->setValue(Settings::REGISTER_PROGRAMS_TYPE, $values['registerProgramsType']);
        $this->settingsRepository->setValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, $values['isAllowedRegisterProgramsBeforePayment']);
        $this->settingsRepository->setDateTimeValue(Settings::REGISTER_PROGRAMS_FROM, $values['registerProgramsFrom']);
        $this->settingsRepository->setDateTimeValue(Settings::REGISTER_PROGRAMS_TO, $values['registerProgramsTo']);
    }

    /**
     * Ověří, že otevření zapisování programů je dříve než uzavření.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRegisterProgramsFrom($field, $args)
    {
        if ($args[0] === NULL || $args[1] == NULL)
            return TRUE;
        return $args[0] < $args[1];
    }

    /**
     * Ověří, že uzavření zapisování programů je později než otevření.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRegisterProgramsTo($field, $args)
    {
        if ($args[0] === NULL || $args[1] == NULL)
            return TRUE;
        return $args[0] > $args[1];
    }

    /**
     * Vrátí stavy registrace programů.
     * @return array
     */
    private function prepareRegisterProgramsTypeOptions()
    {
        $options = [];
        foreach (RegisterProgramsType::$types as $type)
            $options[$type] = 'common.register_programs_type.' . $type;
        return $options;
    }
}
