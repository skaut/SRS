<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Services\ISettingsService;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationFormFactory
{
    use Nette\SmartObject;

    private BaseFormFactory $baseFormFactory;

    private ISettingsService $settingsService;

    public function __construct(BaseFormFactory $baseForm, ISettingsService $settingsService)
    {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addTextArea('applicationAgreement', 'admin.configuration.application_agreement')
            ->setHtmlAttribute('rows', 5);

        $editCustomInputsToDate = new DateControl('admin.configuration.application_edit_custom_inputs_to');
        $editCustomInputsToDate->addRule(Form::FILLED, 'admin.configuration.application_edit_custom_inputs_to_empty');
        $form->addComponent($editCustomInputsToDate, 'editCustomInputsTo');

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
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->settingsService->setValue(Settings::APPLICATION_AGREEMENT, $values->applicationAgreement);
        $this->settingsService->setDateValue(Settings::EDIT_CUSTOM_INPUTS_TO, $values->editCustomInputsTo);
    }
}
