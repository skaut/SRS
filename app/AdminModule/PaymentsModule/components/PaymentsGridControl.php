<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Components;

use App\Model\Enums\PaymentState;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentRepository;
use App\Model\Settings\SettingsException;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\PdfExportService;
use App\Utils\Helpers;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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

    /** @var ApplicationService */
    private $applicationService;

    /** @var PdfExportService */
    private $pdfExportService;


    public function __construct(
        Translator $translator,
        PaymentRepository $paymentRepository,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        ApplicationService $applicationService,
        PdfExportService $pdfExportService
    ) {
        parent::__construct();

        $this->translator            = $translator;
        $this->paymentRepository     = $paymentRepository;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository        = $userRepository;
        $this->applicationService    = $applicationService;
        $this->pdfExportService      = $pdfExportService;
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
     */
    public function createComponentPaymentsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->paymentRepository->createQueryBuilder('p'));
        $grid->setDefaultSort(['date' => 'DESC']);
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);

        $grid->addColumnDateTime('date', 'admin.payments.payments.date')
            ->setFormat(Helpers::DATETIME_FORMAT)
            ->setSortable();

        $grid->addColumnNumber('amount', 'admin.payments.payments.amount')
            ->setFormat(2, ',', ' ')
            ->setSortable();

        $grid->addColumnText('variableSymbol', 'admin.payments.payments.variable_symbol')
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

        $grid->addInlineAdd()->onControlAdd[] = function (Container $container) : void {
            $container->addDatePicker('date', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');

            $container->addInteger('amount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.amount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.amount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');
        };
        $grid->getInlineAdd()->onSubmit[]     = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container) : void {
            $container->addDatePicker('date', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');

            $container->addInteger('amount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.amount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.amount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');

            $container->addMultiSelect('pairedApplications', '', $this->applicationRepository->getApplicationsVariableSymbolsOptions())
                ->setAttribute('class', 'datagrid-multiselect')
                ->setAttribute('data-live-search', 'true');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Payment $payment) : void {
            $container['pairedApplications']->setItems($this->applicationRepository->getWaitingForPaymentOrPairedApplicationsVariableSymbolsOptions($payment->getPairedValidApplications()));

            $container->setDefaults([
                'date' => $payment->getDate(),
                'amount' => $payment->getAmount(),
                'variableSymbol' => $payment->getVariableSymbol(),
                'pairedApplications' => $this->applicationRepository->findApplicationsIds($payment->getPairedValidApplications()),
            ]);

            if ($payment->getTransactionId() === null) {
                return;
            }

            $container['date']->setDisabled();
            $container['amount']->setDisabled();
            $container['variableSymbol']->setDisabled();
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('generatePaymentProofBank', 'admin.payments.payments.download_payment_proof_bank');
        $grid->allowRowsAction('generatePaymentProofBank', function (Payment $payment) {
            return $payment->getState() === PaymentState::PAIRED_AUTO || $payment->getState() === PaymentState::PAIRED_MANUAL;
        });

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.payments.payments.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání platby.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function add(\stdClass $values) : void
    {
        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->applicationService->createPayment($values['date'], $values['amount'], $values['variableSymbol'], null, null, null, null, $loggedUser);

        $this->getPresenter()->flashMessage('admin.payments.payments.saved', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu platby.
     * @throws \Throwable
     */
    public function edit(int $id, \stdClass $values) : void
    {
        $payment = $this->paymentRepository->findById($id);

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $pairedApplications = $this->applicationRepository->findApplicationsByIds($values['pairedApplications']);

        $this->applicationService->updatePayment($payment, $values['date'], $values['amount'], $values['variableSymbol'], $pairedApplications, $loggedUser);

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

        $this->getPresenter()->flashMessage('admin.payments.payments.saved.deleted', 'success');
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
     * Vrátí stavy plateb jako možnosti pro select.
     * @return string[]
     */
    private function preparePaymentStatesOptions() : array
    {
        $options     = [];
        $options[''] = '';
        foreach (PaymentState::$states as $state) {
            $options[$state] = 'common.payment_state.' . $state;
        }
        return $options;
    }
}
