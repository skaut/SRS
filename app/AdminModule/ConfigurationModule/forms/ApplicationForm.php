<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Services\SettingsService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ApplicationForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;


    public function __construct(BaseForm $baseForm, SettingsService $settingsService)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('applicationAgreement', 'admin.configuration.application_agreement')
            ->setAttribute('rows', 5);

        $form->addDatePicker('editCustomInputsTo', 'admin.configuration.application_edit_custom_inputs_to')
            ->addRule(Form::FILLED, 'admin.configuration.application_edit_custom_inputs_to_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'applicationAgreement' => $this->settingsService->getValue(Settings::APPLICATION_AGREEMENT),
            'editCustomInputsTo' => $this->settingsService->getDateValue(Settings::EDIT_CUSTOM_INPUTS_TO),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $this->settingsService->setValue(Settings::APPLICATION_AGREEMENT, $values['applicationAgreement']);
        $this->settingsService->setValue(Settings::EDIT_CUSTOM_INPUTS_TO, (string) $values['editCustomInputsTo']);
    }
}
