<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Components;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Enums\PaymentState;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\PaymentRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\ApplicationService;
use App\Services\BankService;
use App\Services\QueryBus;
use App\Utils\Helpers;
use DateTimeImmutable;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\Session;
use Nette\Localization\Translator;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;

/**
 * Komponenta pro správu plateb.
 */
class PaymentsGridControl extends Control
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly Translator $translator,
        private readonly PaymentRepository $paymentRepository,
        private readonly UserRepository $userRepository,
        private readonly ApplicationService $applicationService,
        private readonly BankService $bankService,
        private readonly Session $session,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/payments_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     * @throws Throwable
     */
    public function createComponentPaymentsGrid(string $name): void
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
            ->setFormat(2, ',')
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
            ->setRenderer(fn (Payment $payment) => $this->translator->translate('common.payment_state.' . $payment->getState()))
            ->setFilterMultiSelect($this->preparePaymentStatesOptions())
            ->setTranslateOptions();

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = static function (Container $container): void {
            $dateDate = new DateControl('');
            $dateDate->setDefaultValue(new DateTimeImmutable())
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');
            $container->addComponent($dateDate, 'date');

            $container->addInteger('amount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.amount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.amount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        if ($this->queryBus->handle(new SettingStringValueQuery(Settings::BANK_TOKEN)) !== null) {
            $grid->addToolbarButton('checkPayments!')
                ->setText('admin.payments.payments.check_payments');
        }

        $grid->addAction('generatePaymentProofBank', 'admin.payments.payments.download_payment_proof_bank');
        $grid->allowRowsAction('generatePaymentProofBank', static fn (Payment $payment) => $payment->getState() === PaymentState::PAIRED_AUTO || $payment->getState() === PaymentState::PAIRED_MANUAL);

        $grid->addAction('edit', 'admin.common.edit', 'Payments:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.payments.payments.delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', static fn (Payment $payment) => $payment->getTransactionId() === null);
    }

    /**
     * Zpracuje přidání platby.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function add(stdClass $values): void
    {
        $p = $this->getPresenter();
        assert($p instanceof AdminBasePresenter);

        $this->applicationService->createPaymentManual($values->date, $values->amount, $values->variableSymbol, $p->getDbUser());

        $p->flashMessage('admin.payments.payments.saved', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Odstraní platbu.
     *
     * @throws Throwable
     */
    public function handleDelete(int $id): void
    {
        $payment = $this->paymentRepository->findById($id);

        $p = $this->getPresenter();
        assert($p instanceof AdminBasePresenter);

        $this->applicationService->removePayment($payment, $p->getDbUser());

        $p->flashMessage('admin.payments.payments.deleted', 'success');
        $p->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     *
     * @throws Throwable
     */
    public function handleGeneratePaymentProofBank(int $id): void
    {
        $this->session->getSection('srs')->applicationIds = Helpers::getIds(
            $this->paymentRepository->findById($id)->getPairedApplications(),
        );
        $this->presenter->redirect(':Export:IncomeProof:applications');
    }

    /**
     * Zkontroluje platby na bankovním účtu.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function handleCheckPayments(): void
    {
        $from = $this->queryBus->handle(new SettingDateValueQuery(Settings::BANK_DOWNLOAD_FROM));
        $this->bankService->downloadTransactions($from);
    }

    /**
     * Vrátí stavy plateb jako možnosti pro select.
     *
     * @return string[]
     */
    private function preparePaymentStatesOptions(): array
    {
        $options = [];
        foreach (PaymentState::$states as $state) {
            $options[$state] = 'common.payment_state.' . $state;
        }

        return $options;
    }
}
