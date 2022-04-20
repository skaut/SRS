<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Settings;
use App\Services\BankService;
use App\Services\CommandBus;
use DateTimeImmutable;
use FioApi\Exceptions\InternalErrorException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;

use function assert;

/**
 * Formulár pro nastavení párování plateb
 */
class BankFormFactory
{
    use Nette\SmartObject;

    public function __construct(private BaseFormFactory $baseFormFactory, private CommandBus $commandBus, private BankService $bankService)
    {
    }

    /**
     * Vytvoří formulář
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
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
     * Zpracuje formulář
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $token = $values->bankToken;
        $from  = $values->bankDownloadFrom;

        try {
            $this->bankService->downloadTransactions($from, $token);
            $this->commandBus->handle(new SetSettingStringValue(Settings::BANK_TOKEN, $token));
        } catch (InternalErrorException $ex) {
            Debugger::log($ex, ILogger::WARNING);
            $bankTokenInput = $form['bankToken'];
            assert($bankTokenInput instanceof TextInput);
            $bankTokenInput->addError('admin.configuration.payment.bank.invalid_token');
        }
    }

    /**
     * Ověří, že datum počátku stahování transakcí je v minulosti
     */
    public function validateBankDownloadFromDate(DateControl $field): bool
    {
        return $field->getValue() <= new DateTimeImmutable();
    }
}
