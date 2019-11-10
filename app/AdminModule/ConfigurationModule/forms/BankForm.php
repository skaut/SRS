<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Services\BankService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FioApi\Exceptions\InternalErrorException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\Forms\Controls\DatePicker;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Formulár pro nastavení párování plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class BankForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var BankService */
    private $bankService;


    public function __construct(
        BaseForm $baseForm,
        SettingsFacade $settingsFacade,
        BankService $bankService
    ) {
        $this->baseFormFactory = $baseForm;
        $this->settingsFacade  = $settingsFacade;
        $this->bankService     = $bankService;
    }

    /**
     * Vytvoří formulář.
     * @throws \Throwable
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

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
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        $token = $values['bankToken'];
        $from  = $values['bankDownloadFrom'];

        try {
            $this->bankService->downloadTransactions($from, $token);
            $this->settingsFacade->setValue(Settings::BANK_TOKEN, $token);
        } catch (InternalErrorException $ex) {
            Debugger::log($ex, ILogger::WARNING);
            $form['bankToken']->addError('admin.configuration.payment.bank.invalid_token');
        }
    }

    /**
     * Ověří, že datum počátku stahování transakcí je v minulosti.
     */
    public function validateBankDownloadFromDate(DatePicker $field) : bool
    {
        return $field->getValue() <= new \DateTime();
    }
}
