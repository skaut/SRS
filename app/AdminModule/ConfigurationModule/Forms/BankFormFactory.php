<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\BankService;
use App\Services\SettingsService;
use DateTimeImmutable;
use FioApi\Exceptions\InternalErrorException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs3FormRenderer;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Formulár pro nastavení párování plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BankFormFactory
{
    use Nette\SmartObject;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;

    /** @var BankService */
    private $bankService;

    public function __construct(
        BaseFormFactory $baseForm,
        SettingsService $settingsService,
        BankService $bankService
    ) {
        $this->baseFormFactory = $baseForm;
        $this->settingsService = $settingsService;
        $this->bankService     = $bankService;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $form->addSelect('bank', 'admin.configuration.payment.bank.bank', ['fio' => 'FIO']);
        $form->addText('bankToken', 'admin.configuration.payment.bank.token')
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank.token_empty')
            ->addRule(Form::LENGTH, 'admin.configuration.payment.bank.token_length', 64);

        $bankDownloadFromDate = new DateControl('admin.configuration.payment.bank.download_from');
        $bankDownloadFromDate
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank.download_from_empty')
            ->addRule([$this, 'validateBankDownloadFromDate'], 'admin.configuration.payment.bank.download_from_future');
        $form->addComponent($bankDownloadFromDate, 'bankDownloadFrom');

        $form->addSubmit('submit', 'admin.common.save');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        $token = $values->bankToken;
        $from  = $values->bankDownloadFrom;

        try {
            $this->bankService->downloadTransactions($from, $token);
            $this->settingsService->setValue(Settings::BANK_TOKEN, $token);
        } catch (InternalErrorException $ex) {
            Debugger::log($ex, ILogger::WARNING);
            /** @var Nette\Forms\Controls\TextInput $bankTokenInput */
            $bankTokenInput = $form['bankToken'];
            $bankTokenInput->addError('admin.configuration.payment.bank.invalid_token');
        }
    }

    /**
     * Ověří, že datum počátku stahování transakcí je v minulosti.
     */
    public function validateBankDownloadFromDate(DateControl $field) : bool
    {
        return $field->getValue() <= new DateTimeImmutable();
    }
}
