<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Services\BankService;
use App\Services\SettingsService;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FioApi\Exceptions\InternalErrorException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\Forms\Controls\DatePicker;
use Nextras\Forms\Rendering\Bs3FormRenderer;
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
     * @throws Throwable
     */
    public function create() : BaseForm
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addSelect('bank', 'admin.configuration.payment.bank.bank', ['fio' => 'FIO']);
        $form->addText('bankToken', 'admin.configuration.payment.bank.token')
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank.token_empty')
            ->addRule(Form::LENGTH, 'admin.configuration.payment.bank.token_length', 64);
        $form->addDatePicker('bankDownloadFrom', 'admin.configuration.payment.bank.download_from')
            ->addRule(Form::FILLED, 'admin.configuration.payment.bank.download_from_empty')
            ->addRule([$this, 'validateBankDownloadFromDate'], 'admin.configuration.payment.bank.download_from_future');

        $form->addSubmit('submit', 'admin.common.save');

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
    public function processForm(BaseForm $form, stdClass $values) : void
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
    public function validateBankDownloadFromDate(DatePicker $field) : bool
    {
        return $field->getValue() <= new DateTime();
    }
}
