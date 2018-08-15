<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;

/**
 * Formulář pro nastavení dokladů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentProofForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsRepository */
    private $settingsRepository;


    public function __construct(BaseForm $baseForm, SettingsRepository $settingsRepository)
    {
        $this->baseFormFactory    = $baseForm;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Vytvoří formulář.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addTextArea('company', 'admin.configuration.company')
            ->addRule(Form::FILLED, 'admin.configuration.company_empty');

        $form->addText('ico', 'admin.configuration.ico')
            ->addRule(Form::FILLED, 'admin.configuration.ico_empty')
            ->addRule(Form::PATTERN, 'admin.configuration.ico_format', '^\d{8}$');

        $form->addText('accountant', 'admin.configuration.accountant')
            ->addRule(Form::FILLED, 'admin.configuration.accountant_empty');

        $form->addText('printLocation', 'admin.configuration.print_location')
            ->addRule(Form::FILLED, 'admin.configuration.print_location_empty');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'company' => $this->settingsRepository->getValue(Settings::COMPANY),
            'ico' => $this->settingsRepository->getValue(Settings::ICO),
            'accountant' => $this->settingsRepository->getValue(Settings::ACCOUNTANT),
            'printLocation' => $this->settingsRepository->getValue(Settings::PRINT_LOCATION),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param array $values
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function processForm(Form $form, array $values) : void
    {
        $this->settingsRepository->setValue(Settings::COMPANY, $values['company']);
        $this->settingsRepository->setValue(Settings::ICO, $values['ico']);
        $this->settingsRepository->setValue(Settings::ACCOUNTANT, $values['accountant']);
        $this->settingsRepository->setValue(Settings::PRINT_LOCATION, $values['printLocation']);
    }
}
