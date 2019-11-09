<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Components;

use App\Model\Enums\PaymentState;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\BankService;
use App\Services\PdfExportService;
use App\Utils\Helpers;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var PaymentRepository */
    private $paymentRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var ApplicationService */
    private $applicationService;

    /** @var PdfExportService */
    private $pdfExportService;

    /** @var BankService */
    private $bankService;


    public function __construct(
        Translator $translator,
        PaymentRepository $paymentRepository,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        SettingsFacade $settingsFacade,
        ApplicationService $applicationService,
        PdfExportService $pdfExportService,
        BankService $bankService
    ) {
        parent::__construct();

        $this->translator            = $translator;
        $this->paymentRepository     = $paymentRepository;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository        = $userRepository;
        $this->settingsFacade    = $settingsFacade;
        $this->applicationService    = $applicationService;
        $this->pdfExportService      = $pdfExportService;
        $this->bankService           = $bankService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/payments_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridException
     * @throws SettingsException
     * @throws \Throwable
     */
    public function createComponentPaymentsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->paymentRepository->createQueryBuilder('p'));
        $grid->setDefaultSort(['date' => 'DESC']);
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addColumnDateTime('date', 'admin.payments.payments.date')
            ->setFormat(Helpers::DATE_FORMAT)
            ->setSortable();

        $grid->addColumnNumber('amount', 'admin.payments.payments.amount')
            ->setFormat(2, ',', ' ')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'admin.payments.payments.variable_symbol')
            ->setFilterText();

        $grid->addColumnText('accountNumber', 'admin.payments.payments.account_number')
            ->setFilterText();

        $grid->addColumnText('accountName', 'admin.payments.payments.account_name')
            ->setFilterText();

        $grid->addColumnText('message', 'admin.payments.payments.message')
            ->setFilterText();

        $grid->addColumnText('pairedApplications', 'admin.payments.payments.paired_applications', 'pairedValidApplicationsText');

        $grid->addColumnText('state', 'admin.payments.payments.state')
            ->setRenderer(function (Payment $payment) {
                return $this->translator->translate('common.payment_state.' . $payment->getState());
            })
            ->setFilterMultiSelect($this->preparePaymentStatesOptions())
            ->setTranslateOptions();

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) : void {
            $container->addDatePicker('date', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');

            $container->addInteger('amount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.amount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.amount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        if ($this->settingsFacade->getValue(Settings::BANK_TOKEN) !== null) {
            $grid->addToolbarButton('checkPayments!')
                ->setText('admin.payments.payments.check_payments');
        }

        $grid->addAction('generatePaymentProofBank', 'admin.payments.payments.download_payment_proof_bank');
        $grid->allowRowsAction('generatePaymentProofBank', function (Payment $payment) {
            return $payment->getState() === PaymentState::PAIRED_AUTO || $payment->getState() === PaymentState::PAIRED_MANUAL;
        });

        $grid->addAction('edit', 'admin.common.edit', 'Payments:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.payments.payments.delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', function (Payment $payment) {
            return $payment->getTransactionId() === null;
        });
    }

    /**
     * Zpracuje přidání platby.
     * @throws AbortException
     * @throws \Throwable
     */
    public function add(\stdClass $values) : void
    {
        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->applicationService->createPayment($values['date'], $values['amount'], $values['variableSymbol'], null, null, null, null, $loggedUser);

        $this->getPresenter()->flashMessage('admin.payments.payments.saved', 'success');
        $this->redirect('this');
    }

    /**
     * Odstraní platbu.
     * @throws \Throwable
     */
    public function handleDelete(int $id) : void
    {
        $payment = $this->paymentRepository->findById($id);

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->applicationService->removePayment($payment, $loggedUser);

        $this->getPresenter()->flashMessage('admin.payments.payments.deleted', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function handleGeneratePaymentProofBank(int $id) : void
    {
        $this->pdfExportService->generateApplicationsPaymentProofs(
            $this->paymentRepository->findById($id)->getPairedValidApplications(),
            'potvrzeni-o-prijeti-platby.pdf',
            $this->userRepository->findById($this->getPresenter()->getUser()->id)
        );
    }

    /**
     * Zkontroluje platby na bankovním účtu.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function handleCheckPayments() : void
    {
        $from = $this->settingsFacade->getDateValue(Settings::BANK_DOWNLOAD_FROM);
        $this->bankService->downloadTransactions($from);
    }

    /**
     * Vrátí stavy plateb jako možnosti pro select.
     * @return string[]
     */
    private function preparePaymentStatesOptions() : array
    {
        $options = [];
        foreach (PaymentState::$states as $state) {
            $options[$state] = 'common.payment_state.' . $state;
        }
        return $options;
    }
}
