<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\Application\Application;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu přihlášek
 */
class ApplicationsGridControl extends Control
{
    private ?User $user = null;

    public function __construct(
        private Translator $translator,
        private EntityManagerInterface $em,
        private ApplicationRepository $applicationRepository,
        private UserRepository $userRepository,
        private SubeventRepository $subeventRepository,
        private ApplicationService $applicationService,
        private SubeventService $subeventService,
        private Validators $validators
    ) {
    }

    /**
     * Vykreslí komponentu
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/applications_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu
     *
     * @throws NonUniqueResultException
     * @throws DataGridException
     * @throws NoResultException
     */
    public function createComponentApplicationsGrid(string $name): void
    {
        $this->user = $this->userRepository->findById((int) $this->getPresenter()->getParameter('id'));

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->applicationRepository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u = :user')
            ->andWhere('a.validTo IS NULL')
            ->setParameter('user', $this->user)
            ->orderBy('a.applicationId'));
        $grid->setPagination(false);

        $grid->setItemsDetail() // todo: schovat, pokud neni nastaveno cislo uctu
            ->setTemplateParameters(['applicationRepository' => $this->applicationRepository]);
        $grid->setTemplateFile(__DIR__ . '/templates/applications_grid_detail.latte');

        $grid->addColumnDateTime('applicationDate', 'admin.users.users_applications_application_date')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnText('roles', 'admin.users.users_applications_roles', 'rolesText');

        $grid->addColumnText('subevents', 'admin.users.users_applications_subevents', 'subeventsText');

        $grid->addColumnNumber('fee', 'admin.users.users_applications_fee');

        $grid->addColumnText('variableSymbol', 'admin.users.users_applications_variable_symbol', 'variableSymbolText');

        $grid->addColumnDateTime('maturityDate', 'admin.users.users_applications_maturity_date')
            ->setFormat(Helpers::DATE_FORMAT);

        $grid->addColumnText('paymentMethod', 'admin.users.users_applications_payment_method')
            ->setRenderer(function (Application $row) {
                $paymentMethod = $row->getPaymentMethod();
                if ($paymentMethod) {
                    return $this->translator->translate('common.payment.' . $paymentMethod);
                }

                return null;
            });

        $grid->addColumnDateTime('paymentDate', 'admin.users.users_applications_payment_date');

        $grid->addColumnText('state', 'admin.users.users_applications_state')
            ->setRenderer(fn (Application $row) => $this->translator->translate('common.application_state.' . $row->getState()));

        if ($explicitSubeventsExists) {
            $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container): void {
                $container->addMultiSelect(
                    'subevents',
                    '',
                    $this->subeventService->getSubeventsOptionsWithCapacity(false, false, true, false, $this->user)
                )->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.users.users_applications_subevents_empty');
            };
            $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];
        }

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container): void {
            $container->addMultiSelect(
                'subevents',
                '',
                $this->subeventService->getSubeventsOptionsWithCapacity(false, false, false, false)
            )->setHtmlAttribute('class', 'datagrid-multiselect');

            $paymentMethodSelect = $container->addSelect(
                'paymentMethod',
                '',
                $this->preparePaymentMethodOptions()
            );

            $paymentDateDate = new DateControl('');
            $container->addComponent($paymentDateDate, 'paymentDate');

            $paymentMethodSelect
                ->addConditionOn($paymentDateDate, Form::FILLED)
                ->addRule(Form::FILLED, 'admin.users.users_applications_payment_method_empty');

            $maturityDateDate = new DateControl('');
            $container->addComponent($maturityDateDate, 'maturityDate');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Application $item): void {
            $container->setDefaults([
                'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents()),
                'paymentMethod' => $item->getPaymentMethod(),
                'paymentDate' => $item->getPaymentDate(),
                'maturityDate' => $item->getMaturityDate(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
        $grid->allowRowsInlineEdit(static fn (Application $item) => ! $item->isCanceled());

        $grid->addAction('generatePaymentProofCash', 'admin.users.users_applications_download_payment_proof_cash');
        $grid->allowRowsAction(
            'generatePaymentProofCash',
            static fn (Application $item) => $item->getState() === ApplicationState::PAID
                && $item->getPaymentMethod() === PaymentType::CASH
                && $item->getPaymentDate()
        );

        $grid->addAction('generatePaymentProofBank', 'admin.users.users_applications_download_payment_proof_bank');
        $grid->allowRowsAction(
            'generatePaymentProofBank',
            static fn (Application $item) => $item->getState() === ApplicationState::PAID
                && $item->getPaymentMethod() === PaymentType::BANK
                && $item->getPaymentDate()
        );

        if ($this->user->getNotCanceledSubeventsApplications()->count() > 1) {
            $grid->addAction('cancelApplication', 'admin.users.users_applications_cancel_application')
                ->addAttributes([
                    'data-toggle' => 'confirmation',
                    'data-content' => $this->translator->translate('admin.users.users_applications_cancel_application_confirm'),
                ])->setClass('btn btn-xs btn-danger');
            $grid->allowRowsAction(
                'cancelApplication',
                static fn (Application $application) => $application instanceof SubeventsApplication &&
                    ! $application->isCanceled()
            );
        }

        $grid->setColumnsSummary(['fee'], static fn (Application $item, $column) => $item->isCanceled() ? 0 : $item->getFee());

        $grid->setRowCallback(static function (Application $application, Html $tr): void {
            if ($application->isCanceled()) {
                $tr->addClass('disabled');
            }
        });
    }

    /**
     * Zpracuje přidání podakcí
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function add(stdClass $values): void
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values->subevents);

        $p = $this->getPresenter();

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
            $p->redrawControl('flashes');

            return;
        }

        if (! $this->validators->validateSubeventsRegistered($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_registered', 'danger');
            $p->redrawControl('flashes');

            return;
        }

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->applicationService->addSubeventsApplication($this->user, $selectedSubevents, $loggedUser);

        $p->flashMessage('admin.users.users_applications_saved', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu přihlášky
     *
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values): void
    {
        $application = $this->applicationRepository->findById((int) $id);

        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values->subevents);

        $p = $this->getPresenter();

        if ($application instanceof RolesApplication) {
            if (! $selectedSubevents->isEmpty()) {
                $p->flashMessage('admin.users.users_applications_subevents_not_empty', 'danger');
                $p->redrawControl('flashes');

                return;
            }
        } else {
            if ($selectedSubevents->isEmpty()) {
                $p->flashMessage('admin.users.users_applications_subevents_empty', 'danger');
                $p->redrawControl('flashes');

                return;
            }
        }

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
            $p->redrawControl('flashes');

            return;
        }

        if (! $this->validators->validateSubeventsRegistered($selectedSubevents, $this->user, $application)) {
            $p->flashMessage('admin.users.users_applications_subevents_registered', 'danger');
            $p->redrawControl('flashes');

            return;
        }

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->em->wrapInTransaction(function () use ($application, $selectedSubevents, $values, $loggedUser): void {
            if ($application instanceof SubeventsApplication) {
                $this->applicationService->updateSubeventsApplication($application, $selectedSubevents, $loggedUser);
            }

            $this->applicationService->updateApplicationPayment(
                $application,
                $values->paymentMethod ?: null,
                $values->paymentDate,
                $values->maturityDate,
                $loggedUser
            );
        });

        $p->flashMessage('admin.users.users_applications_saved', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Vygeneruje příjmový pokladní doklad
     *
     * @throws Throwable
     */
    public function handleGeneratePaymentProofCash(int $id): void
    {
        $this->presenter->redirect(':Export:IncomeProof:application', ['id' => $id]);
    }

    /**
     * Vygeneruje potvrzení o přijetí platby
     *
     * @throws Throwable
     */
    public function handleGeneratePaymentProofBank(int $id): void
    {
        $this->presenter->redirect(':Export:IncomeProof:application', ['id' => $id]);
    }

    /**
     * Zruší přihlášku
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleCancelApplication(int $id): void
    {
        $application = $this->applicationRepository->findById($id);

        $p = $this->getPresenter();

        if ($application instanceof SubeventsApplication && ! $application->isCanceled()) {
            $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);
            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED, $loggedUser);
            $p->flashMessage('admin.users.users_applications_application_canceled', 'success');
        }

        $p->redirect('this');
    }

    /**
     * Vrátí platební metody jako možnosti pro select
     *
     * @return string[]
     */
    private function preparePaymentMethodOptions(): array
    {
        $options     = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }

        return $options;
    }
}
