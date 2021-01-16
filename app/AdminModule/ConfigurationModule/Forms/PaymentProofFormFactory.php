<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Services\ISettingsService;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use stdClass;
use Throwable;

/**
 * Formulář pro nastavení dokladů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentProofFormFactory
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

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addTextArea('company', 'admin.configuration.company')
            ->addRule(Form::FILLED, 'admin.configuration.company_empty');

        $form->addText('ico', 'admin.configuration.ico')
            ->addRule(Form::FILLED, 'admin.configuration.ico_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.ico_format', '^\d{8}$');

        $form->addText('accountant', 'admin.configuration.accountant')
            ->addRule(Form::FILLED, 'admin.configuration.accountant_empty');

//        $form->addText('printLocation', 'admin.configuration.print_location') todo: odstranit, pokud se nebude pouzivat v dokladech
//            ->addRule(Form::FILLED, 'admin.configuration.print_location_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'company' => $this->settingsService->getValue(Settings::COMPANY),
            'ico' => $this->settingsService->getValue(Settings::ICO),
            'accountant' => $this->settingsService->getValue(Settings::ACCOUNTANT),
//            'printLocation' => $this->settingsService->getValue(Settings::PRINT_LOCATION), todo: odstranit, pokud se nebude pouzivat v dokladech
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
        $this->settingsService->setValue(Settings::COMPANY, $values->company);
        $this->settingsService->setValue(Settings::ICO, $values->ico);
        $this->settingsService->setValue(Settings::ACCOUNTANT, $values->accountant);
//        $this->settingsService->setValue(Settings::PRINT_LOCATION, $values->printLocation); todo: odstranit, pokud se nebude pouzivat v dokladech
    }
}
