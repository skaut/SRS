<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Application\Application;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Application\Repositories\RolesApplicationRepository;
use App\Model\Application\Repositories\SubeventsApplicationRepository;
use App\Model\Application\SubeventsApplication;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use App\Services\QueryBus;
use App\Services\SubeventService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Komponenta pro správu vlastních přihlášek.
 */
class ApplicationsGridControl extends BaseContentControl
{
    private ?User $user = null;

    public function __construct(
        private QueryBus $queryBus,
        private Translator $translator,
        private ApplicationRepository $applicationRepository,
        private UserRepository $userRepository,
        private SubeventRepository $subeventRepository,
        private ApplicationService $applicationService,
        private Validators $validators,
        private RolesApplicationRepository $rolesApplicationRepository,
        private SubeventsApplicationRepository $subeventsApplicationRepository,
        private SubeventService $subeventService
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/applications_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws SettingsItemNotFoundException
     * @throws NonUniqueResultException
     * @throws Throwable
     * @throws DataGridException
     */
    public function createComponentApplicationsGrid(string $name): void
    {
        $this->user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();
        $userHasFixedFeeRole     = $this->user->hasFixedFeeRole();

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);

        if (! $explicitSubeventsExists) {
            $qb = $this->rolesApplicationRepository;
        } elseif (! $userHasFixedFeeRole) {
            $qb = $this->subeventsApplicationRepository;
        } else {
            $qb = $this->applicationRepository;
        }

        $qb = $qb->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u = :user')
            ->andWhere('a.validTo IS NULL')
            ->setParameter('user', $this->user)
            ->orderBy('a.applicationId');

        $grid->setDataSource($qb);
        $grid->setPagination(false);

        $grid->addColumnDateTime('applicationDate', 'web.profile.applications_application_date')
            ->setFormat(Helpers::DATETIME_FORMAT);

        if ($userHasFixedFeeRole) {
            $grid->addColumnText('roles', 'web.profile.applications_roles', 'rolesText');
        }

        if ($explicitSubeventsExists) {
            $grid->addColumnText('subevents', 'web.profile.applications_subevents', 'subeventsText');
        }

        $grid->addColumnNumber('fee', 'web.profile.applications_fee');

        $grid->addColumnText('variable_symbol', 'web.profile.applications_variable_symbol', 'variableSymbolText');

        $grid->addColumnDateTime('maturityDate', 'web.profile.applications_maturity_date')
            ->setFormat(Helpers::DATE_FORMAT);

        $grid->addColumnText('state', 'web.profile.applications_state')
            ->setRenderer(fn (Application $row) => $this->applicationService->getStateText($row));

        if ($explicitSubeventsExists) {
            if ($this->applicationService->isAllowedAddApplication($this->user)) {
                $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container): void {
                    $options = $this->subeventService->getSubeventsOptionsWithCapacity(true, true, true, false, $this->user);
                    $container->addMultiSelect('subevents', '', $options)
                        ->setHtmlAttribute('class', 'datagrid-multiselect')
                        ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
                };
                $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
                $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
            }

            $grid->addInlineEdit()->onControlAdd[] = function (Container $container): void {
                $options = $this->subeventService->getSubeventsOptionsWithCapacity(true, true, false, true, $this->user);
                $container->addMultiSelect('subevents', '', $options)
                    ->setHtmlAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            };
            $grid->getInlineEdit()->setText($this->translator->translate('web.profile.applications_edit'));
            $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, SubeventsApplication $item): void {
                $container->setDefaults([
                    'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents()),
                ]);
            };
            $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
            $grid->allowRowsInlineEdit(fn (Application $item) => $this->applicationService->isAllowedEditApplication($item));
        }

        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', static fn (Application $item) => $item->getState() === ApplicationState::PAID
            && $item->getPaymentMethod() === PaymentType::BANK
            && $item->getPaymentDate());

        if ($this->user->getNotCanceledSubeventsApplications()->count() > 1) {
            $grid->addAction('cancelApplication', 'web.profile.applications_cancel_application')
                ->addAttributes([
                    'data-toggle' => 'confirmation',
                    'data-content' => $this->translator->translate('web.profile.applications_cancel_application_confirm'),
                ])->setClass('btn btn-xs btn-danger');
            $grid->allowRowsAction('cancelApplication', fn (Application $item) => $this->applicationService->isAllowedEditApplication($item));
        }

        $grid->setItemsDetail()
            ->setRenderCondition(static fn (Application $item) => $item->isWaitingForPayment())
            ->setText($this->translator->translate('web.profile.applications_pay'))
            ->setIcon('money')
            ->setClass('btn btn-xs btn-primary ajax')
            ->setTemplateParameters([
                'account' => $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER)),
                'message' => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
            ]);
        $grid->setTemplateFile(__DIR__ . '/templates/applications_grid_detail.latte');

        $grid->setColumnsSummary(['fee'], static fn (Application $item, $column) => $item->isCanceled() ? 0 : $item->getFee());

        $grid->setRowCallback(static function (Application $application, Html $tr): void {
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
    public function add(stdClass $values): void
    {
        $selectedSubevents         = $this->subeventRepository->findSubeventsByIds($values->subevents);
        $selectedAndUsersSubevents = clone $this->user->getSubevents();
        foreach ($selectedSubevents as $subevent) {
            $selectedAndUsersSubevents->add($subevent);
        }

        $p = $this->getPresenter();

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $p->redrawControl('flashes');

            return;
        }

        foreach ($this->subeventRepository->findFilteredSubevents(true, false, false, false) as $subevent) {
            if (! $this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate(
                    'web.profile.applications_incompatible_subevents_selected',
                    null,
                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                );
                $p->flashMessage($message, 'danger');
                $p->redrawControl('flashes');

                return;
            }

            if (! $this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate(
                    'web.profile.applications_required_subevents_not_selected',
                    null,
                    ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
                );
                $p->flashMessage($message, 'danger');
                $p->redrawControl('flashes');

                return;
            }
        }

        $this->applicationService->addSubeventsApplication($this->user, $selectedSubevents, $this->user);

        $p->flashMessage('web.profile.applications_add_subevents_successful', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu přihlášky.
     *
     * @throws SettingsItemNotFoundException
     * @throws AbortException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function edit(string $id, stdClass $values): void
    {
        $application = $this->applicationRepository->findById((int) $id);

        if ($application instanceof SubeventsApplication) {
            $selectedSubevents         = $this->subeventRepository->findSubeventsByIds($values->subevents);
            $selectedAndUsersSubevents = clone $this->user->getSubevents();
            foreach ($selectedSubevents as $subevent) {
                $selectedAndUsersSubevents->add($subevent);
            }

            foreach ($application->getSubevents() as $subevent) {
                $selectedAndUsersSubevents->removeElement($subevent);
            }

            $p = $this->getPresenter();

            if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
                $p->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
                $p->redrawControl('flashes');

                return;
            }

            foreach ($this->subeventRepository->findFilteredSubevents(true, false, false, false) as $subevent) {
                if (! $this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                    $message = $this->translator->translate(
                        'web.profile.applications_incompatible_subevents_selected',
                        null,
                        ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                    );
                    $p->flashMessage($message, 'danger');
                    $p->redrawControl('flashes');

                    return;
                }

                if (! $this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                    $message = $this->translator->translate(
                        'web.profile.applications_required_subevents_not_selected',
                        null,
                        ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
                    );
                    $p->flashMessage($message, 'danger');
                    $p->redrawControl('flashes');

                    return;
                }
            }

            $this->applicationService->updateSubeventsApplication($application, $selectedSubevents, $this->user);

            $p->flashMessage('web.profile.applications_edit_successful', 'success');
            $p->redrawControl('flashes');
        }
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     *
     * @throws AbortException
     */
    public function handleGeneratePaymentProofBank(int $id): void
    {
        $this->presenter->redirect(':Export:IncomeProof:application', ['id' => $id]);
    }

    /**
     * Zruší přihlášku.
     *
     * @throws SettingsItemNotFoundException
     * @throws AbortException
     * @throws Throwable
     */
    public function handleCancelApplication(int $id): void
    {
        $application = $this->applicationRepository->findById($id);

        $p = $this->getPresenter();

        if ($application instanceof SubeventsApplication && $this->applicationService->isAllowedEditApplication($application)) {
            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED, $application->getUser());
            $p->flashMessage('web.profile.applications_application_canceled', 'success');
        }

        $p->redirect('this');
    }
}
