<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application\Application;
use App\Model\User\Application\ApplicationRepository;
use App\Model\User\Application\SubeventsApplication;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\Session;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;
use Nettrine\ORM\EntityManagerDecorator;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ApplicationsGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var User */
    private $user;

    /** @var SubeventService */
    private $subeventService;

    /** @var Validators */
    private $validators;

    public function __construct(
        ITranslator $translator,
        EntityManagerDecorator $em,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        SubeventRepository $subeventRepository,
        ApplicationService $applicationService,
        SubeventService $subeventService,
        Validators $validators
    ) {
        $this->translator            = $translator;
        $this->em                    = $em;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository        = $userRepository;
        $this->subeventRepository    = $subeventRepository;
        $this->applicationService    = $applicationService;
        $this->subeventService       = $subeventService;
        $this->validators            = $validators;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/applications_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws NonUniqueResultException
     * @throws DataGridException
     * @throws NoResultException
     */
    public function createComponentApplicationsGrid(string $name) : void
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

        $grid->setItemsDetail()
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
            ->setRenderer(function (Application $row) {
                return $this->translator->translate('common.application_state.' . $row->getState());
            });

        if ($explicitSubeventsExists) {
            $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) : void {
                $container->addMultiSelect(
                    'subevents',
                    '',
                    $this->subeventService->getSubeventsOptionsWithCapacity(false, false, true, false, $this->user)
                )
                    ->setHtmlAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'admin.users.users_applications_subevents_empty');
            };
            $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];
        }

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container) : void {
            $container->addMultiSelect(
                'subevents',
                '',
                $this->subeventService->getSubeventsOptionsWithCapacity(false, false, false, false)
            )
                ->setHtmlAttribute('class', 'datagrid-multiselect');

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
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Application $item) : void {
            $container->setDefaults([
                'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents()),
                'paymentMethod' => $item->getPaymentMethod(),
                'paymentDate' => $item->getPaymentDate(),
                'maturityDate' => $item->getMaturityDate(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
        $grid->allowRowsInlineEdit(static function (Application $item) {
            return ! $item->isCanceled();
        });

        $grid->addAction('generatePaymentProofCash', 'admin.users.users_applications_download_payment_proof_cash');
        $grid->allowRowsAction('generatePaymentProofCash', static function (Application $item) {
            return $item->getState() === ApplicationState::PAID
                && $item->getPaymentMethod() === PaymentType::CASH
                && $item->getPaymentDate();
        });

        $grid->addAction('generatePaymentProofBank', 'admin.users.users_applications_download_payment_proof_bank');
        $grid->allowRowsAction('generatePaymentProofBank', static function (Application $item) {
            return $item->getState() === ApplicationState::PAID
                && $item->getPaymentMethod() === PaymentType::BANK
                && $item->getPaymentDate();
        });

        if ($this->user->getNotCanceledSubeventsApplications()->count() > 1) {
            $grid->addAction('cancelApplication', 'admin.users.users_applications_cancel_application')
                ->addAttributes([
                    'data-toggle' => 'confirmation',
                    'data-content' => $this->translator->translate('admin.users.users_applications_cancel_application_confirm'),
                ])->setClass('btn btn-xs btn-danger');
            $grid->allowRowsAction('cancelApplication', static function (Application $item) {
                return $item->getType() === Application::SUBEVENTS && ! $item->isCanceled();
            });
        }

        $grid->setColumnsSummary(['fee'], static function (Application $item, $column) {
            return $item->isCanceled() ? 0 : $item->getFee();
        });

        $grid->setRowCallback(static function (Application $application, Html $tr) : void {
            if ($application->isCanceled()) {
                $tr->addClass('disabled');
            }
        });
    }

    /**
     * Zpracuje přidání podakcí.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function add(stdClass $values) : void
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values->subevents);

        $p = $this->getPresenter();

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
            $this->redirect('this');
        }

        if (! $this->validators->validateSubeventsRegistered($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_registered', 'danger');
            $this->redirect('this');
        }

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->applicationService->addSubeventsApplication($this->user, $selectedSubevents, $loggedUser);

        $p->flashMessage('admin.users.users_applications_saved', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu přihlášky.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values) : void
    {
        $application = $this->applicationRepository->findById((int) $id);

        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values->subevents);

        $p = $this->getPresenter();

        if ($application->getType() === Application::ROLES) {
            if (! $selectedSubevents->isEmpty()) {
                $p->flashMessage('admin.users.users_applications_subevents_not_empty', 'danger');
                $this->redirect('this');
            }
        } else {
            if ($selectedSubevents->isEmpty()) {
                $p->flashMessage('admin.users.users_applications_subevents_empty', 'danger');
                $this->redirect('this');
            }
        }

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
            $this->redirect('this');
        }

        if (! $this->validators->validateSubeventsRegistered($selectedSubevents, $this->user, $application)) {
            $p->flashMessage('admin.users.users_applications_subevents_registered', 'danger');
            $this->redirect('this');
        }

        $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);

        $this->em->transactional(function () use ($application, $selectedSubevents, $values, $loggedUser) : void {
            if ($application instanceof SubeventsApplication) {
                $this->applicationService->updateSubeventsApplication($application, $selectedSubevents, $loggedUser);
            }

            $this->applicationService->updateApplicationPayment(
                $application,
                $values->paymentMethod ?: null,
                $values->paymentDate,
                $values->maturityDate,
                $application->getIncomeProof(),
                $loggedUser
            );
        });

        $p->flashMessage('admin.users.users_applications_saved', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje příjmový pokladní doklad.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function handleGeneratePaymentProofCash(int $id) : void
    {
        $this->presenter->redirect(':Export:IncomeProof:application', ['id' => $id]);
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function handleGeneratePaymentProofBank(int $id) : void
    {
        $this->presenter->redirect(':Export:IncomeProof:application', ['id' => $id]);
    }

    /**
     * Zruší přihlášku.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleCancelApplication(int $id) : void
    {
        $application = $this->applicationRepository->findById($id);

        if ($application instanceof SubeventsApplication && ! $application->isCanceled()) {
            $loggedUser = $this->userRepository->findById($this->getPresenter()->user->id);
            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED, $loggedUser);
            $this->getPresenter()->flashMessage('admin.users.users_applications_application_canceled', 'success');
        }

        $this->redirect('this');
    }

    /**
     * Vrátí platební metody jako možnosti pro select.
     *
     * @return string[]
     */
    private function preparePaymentMethodOptions() : array
    {
        $options     = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }

        return $options;
    }
}
