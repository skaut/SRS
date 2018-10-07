<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\ProgramService;
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


    public function __construct(
        Translator $translator,
        PaymentRepository $paymentRepository,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        ApplicationService $applicationService
    ) {
        parent::__construct();

        $this->translator            = $translator;
        $this->paymentRepository     = $paymentRepository;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository        = $userRepository;
        $this->applicationService    = $applicationService;
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

        $grid->addColumnDateTime('date', 'admin.payments.payments.date');

        $grid->addColumnNumber('ammount', 'admin.payments.payments.ammount');

        $grid->addColumnText('variableSymbol', 'admin.payments.payments.variable_symbol');

        $grid->addColumnText('accountName', 'admin.payments.payments.account_name');

        $grid->addColumnText('message', 'admin.payments.payments.message');

        $grid->addColumnText('pairedApplications', 'admin.payments.payments.paired_applications', 'pairedApplicationsText');

        $grid->addColumnText('state', 'admin.payments.payments.state');

        $grid->addInlineAdd()->onControlAdd[] = function (Container $container) : void {
            $container->addDatePicker('date', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');

            $container->addInteger('ammount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.ammount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.ammount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');
        };
        $grid->getInlineAdd()->onSubmit[]     = [$this, 'add'];

        $applicationsOptions = null; //todo

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container) use ($applicationsOptions) : void {
            $container->addDatePicker('date', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.date_empty');

            $container->addInteger('ammount', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.ammount_empty')
                ->addRule(Form::MIN, 'admin.payments.payments.ammount_low', 1);

            $container->addText('variableSymbol', '')
                ->addRule(Form::FILLED, 'admin.payments.payments.variable_symbol_empty');

            $container->addMultiSelect('pairedApplications', '', $applicationsOptions)->setAttribute('class', 'datagrid-multiselect');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Payment $item) : void {
            $container->setDefaults([
                'date' => $item->getDate(),
                'ammount' => $item->getAmmount(),
                'variableSymbol' => $item->getVariableSymbol(),
                'pairedApplications' => $this->applicationRepository->findApplicationsIds($item->getPairedApplications()),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
//
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

        $this->applicationService->createPayment($values['date'], $values['ammount'], $values['variableSymbol'], null, null, null, null, $loggedUser);

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

        $this->applicationService->updatePayment($payment, $values['date'], $values['ammount'], $values['variableSymbol'], $pairedApplications, $loggedUser);

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
}
