<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplicationRepository;
use App\Model\User\SubeventsApplication;
use App\Model\User\SubeventsApplicationRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\Services\ProgramService;
use App\Services\SettingsService;
use App\Services\SubeventService;
use App\Services\UserService;
use App\Utils\Helpers;
use App\Utils\Validators;
use Doctrine\ORM\NonUniqueResultException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Komponenta pro správu vlastních přihlášek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var MailService */
    private $mailService;

    /** @var SettingsService */
    private $settingsService;

    /** @var Authenticator */
    private $authenticator;

    /** @var User */
    private $user;

    /** @var PdfExportService */
    private $pdfExportService;

    /** @var ProgramService */
    private $programService;

    /** @var UserService */
    private $userService;

    /** @var Validators */
    private $validators;

    /** @var RolesApplicationRepository */
    private $rolesApplicationRepository;

    /** @var SubeventsApplicationRepository */
    private $subeventsApplicationRepository;

    /** @var SubeventService */
    private $subeventService;


    public function __construct(
        Translator $translator,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository,
        ApplicationService $applicationService,
        ProgramRepository $programRepository,
        MailService $mailService,
        SettingsService $settingsService,
        Authenticator $authenticator,
        PdfExportService $pdfExportService,
        ProgramService $programService,
        UserService $userService,
        Validators $validators,
        RolesApplicationRepository $rolesApplicationRepository,
        SubeventsApplicationRepository $subeventsApplicationRepository,
        SubeventService $subeventService
    ) {
        parent::__construct();

        $this->translator                     = $translator;
        $this->applicationRepository          = $applicationRepository;
        $this->userRepository                 = $userRepository;
        $this->roleRepository                 = $roleRepository;
        $this->subeventRepository             = $subeventRepository;
        $this->applicationService             = $applicationService;
        $this->programRepository              = $programRepository;
        $this->mailService                    = $mailService;
        $this->settingsService                = $settingsService;
        $this->authenticator                  = $authenticator;
        $this->pdfExportService               = $pdfExportService;
        $this->programService                 = $programService;
        $this->userService                    = $userService;
        $this->validators                     = $validators;
        $this->rolesApplicationRepository     = $rolesApplicationRepository;
        $this->subeventsApplicationRepository = $subeventsApplicationRepository;
        $this->subeventService                = $subeventService;
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
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws Throwable
     * @throws DataGridException
     */
    public function createComponentApplicationsGrid(string $name) : void
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
            ->setRenderer(function (Application $row) {
                return $this->applicationService->getStateText($row);
            });

        if ($explicitSubeventsExists) {
            if ($this->applicationService->isAllowedAddApplication($this->user)) {
                $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function ($container) : void {
                    $options = $this->subeventService->getNonRegisteredExplicitOptionsWithCapacity($this->user);
                    $container->addMultiSelect('subevents', '', $options)
                        ->setAttribute('class', 'datagrid-multiselect')
                        ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
                };
                $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
                $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
            }

            $grid->addInlineEdit()->onControlAdd[] = function ($container) : void {
                $options = $this->subeventService->getExplicitOptionsWithCapacity();
                $container->addMultiSelect('subevents', '', $options)
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            };
            $grid->getInlineEdit()->setText($this->translator->translate('web.profile.applications_edit'));
            $grid->getInlineEdit()->onSetDefaults[] = function ($container, SubeventsApplication $item) : void {
                $container->setDefaults([
                    'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents()),
                ]);
            };
            $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
            $grid->allowRowsInlineEdit(function (Application $item) {
                return $this->applicationService->isAllowedEditApplication($item);
            });
        }

        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', function (Application $item) {
            return $item->getState() === ApplicationState::PAID
                && $item->getPaymentMethod() === PaymentType::BANK
                && $item->getPaymentDate();
        });

        if ($this->user->getNotCanceledSubeventsApplications()->count() > 1) {
            $grid->addAction('cancelApplication', 'web.profile.applications_cancel_application')
                ->addAttributes([
                    'data-toggle' => 'confirmation',
                    'data-content' => $this->translator->translate('web.profile.applications_cancel_application_confirm'),
                ])->setClass('btn btn-xs btn-danger');
            $grid->allowRowsAction('cancelApplication', function (Application $item) {
                return $this->applicationService->isAllowedEditApplication($item);
            });
        }

        $grid->setColumnsSummary(['fee'], function (Application $item, $column) {
            return $item->isCanceled() ? 0 : $item->getFee();
        });
    }

    /**
     * Zpracuje přidání podakcí.
     * @throws AbortException
     * @throws Throwable
     */
    public function add(stdClass $values) : void
    {
        $selectedSubevents         = $this->subeventRepository->findSubeventsByIds($values->subevents);
        $selectedAndUsersSubevents = clone $this->user->getSubevents();
        foreach ($selectedSubevents as $subevent) {
            $selectedAndUsersSubevents->add($subevent);
        }

        $p = $this->getPresenter();

        if (! $this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            if (! $this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate(
                    'web.profile.applications_incompatible_subevents_selected',
                    null,
                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
            if ($this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                continue;
            }

            $message = $this->translator->translate(
                'web.profile.applications_required_subevents_not_selected',
                null,
                ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
            );
            $p->flashMessage($message, 'danger');
            $this->redirect('this');
        }

        $this->applicationService->addSubeventsApplication($this->user, $selectedSubevents, $this->user);

        $p->flashMessage('web.profile.applications_add_subevents_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu přihlášky.
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function edit(int $id, stdClass $values) : void
    {
        $application = $this->applicationRepository->findById($id);

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
            $this->redirect('this');
        }

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            if (! $this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate(
                    'web.profile.applications_incompatible_subevents_selected',
                    null,
                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
            if ($this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                continue;
            }

            $message = $this->translator->translate(
                'web.profile.applications_required_subevents_not_selected',
                null,
                ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
            );
            $p->flashMessage($message, 'danger');
            $this->redirect('this');
        }

        $this->applicationService->updateSubeventsApplication($application, $selectedSubevents, $this->user);

        $p->flashMessage('web.profile.applications_edit_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     * @throws SettingsException
     * @throws Throwable
     */
    public function handleGeneratePaymentProofBank(int $id) : void
    {
        $this->pdfExportService->generateApplicationsPaymentProof(
            $this->applicationRepository->findById($id),
            'potvrzeni-o-prijeti-platby.pdf',
            $this->userRepository->findById($this->getPresenter()->getUser()->id)
        );
    }

    /**
     * Zruší přihlášku.
     * @throws SettingsException
     * @throws AbortException
     * @throws Throwable
     */
    public function handleCancelApplication(int $id) : void
    {
        $application = $this->applicationRepository->findById($id);

        if ($this->applicationService->isAllowedEditApplication($application)) {
            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED, $application->getUser());
            $this->getPresenter()->flashMessage('web.profile.applications_application_canceled', 'success');
        }

        $this->redirect('this');
    }
}
