<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;

/**
 * Formulář pro nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsFacade */
    private $settingsFacade;

    public function __construct(BaseForm $baseForm, SettingsFacade $settingsFacade)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsFacade  = $settingsFacade;
        $this->settingsFacade  = $settingsFacade;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws \Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('applicationAgreement', 'admin.configuration.application_agreement')
                ->setAttribute('rows', 5);

        $form->addDatePicker('editCustomInputsTo', 'admin.configuration.application_edit_custom_inputs_to')
                ->addRule(Form::FILLED, 'admin.configuration.application_edit_custom_inputs_to_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults(
            [
                    'applicationAgreement' => $this->settingsFacade->getValue(Settings::APPLICATION_AGREEMENT),
                    'editCustomInputsTo' => $this->settingsFacade->getDateValue(Settings::EDIT_CUSTOM_INPUTS_TO),
                ]
        );

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        $this->settingsFacade->setValue(Settings::APPLICATION_AGREEMENT, $values['applicationAgreement']);
        $this->settingsFacade->setValue(Settings::EDIT_CUSTOM_INPUTS_TO, (string) $values['editCustomInputsTo']);
    }
}
