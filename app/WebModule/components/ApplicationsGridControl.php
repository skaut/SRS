<?php

namespace App\WebModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\SettingsRepository;
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
use App\Services\UserService;
use App\Utils\Validators;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


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

    /** @var SettingsRepository */
    private $settingsRepository;

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


    /**
     * ApplicationsGridControl constructor.
     * @param Translator $translator
     * @param ApplicationRepository $applicationRepository
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SubeventRepository $subeventRepository
     * @param ApplicationService $applicationService
     * @param ProgramRepository $programRepository
     * @param MailService $mailService
     * @param SettingsRepository $settingsRepository
     * @param Authenticator $authenticator
     * @param PdfExportService $pdfExportService
     * @param ProgramService $programService
     * @param UserService $userService
     * @param Validators $validators
     * @param RolesApplicationRepository $rolesApplicationRepository
     * @param SubeventsApplicationRepository $subeventsApplicationRepository
     */
    public function __construct(Translator $translator, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SubeventRepository $subeventRepository, ApplicationService $applicationService,
                                ProgramRepository $programRepository, MailService $mailService,
                                SettingsRepository $settingsRepository, Authenticator $authenticator,
                                PdfExportService $pdfExportService, ProgramService $programService,
                                UserService $userService, Validators $validators,
                                RolesApplicationRepository $rolesApplicationRepository,
                                SubeventsApplicationRepository $subeventsApplicationRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->subeventRepository = $subeventRepository;
        $this->applicationService = $applicationService;
        $this->programRepository = $programRepository;
        $this->mailService = $mailService;
        $this->settingsRepository = $settingsRepository;
        $this->authenticator = $authenticator;
        $this->pdfExportService = $pdfExportService;
        $this->programService = $programService;
        $this->userService = $userService;
        $this->validators = $validators;
        $this->rolesApplicationRepository = $rolesApplicationRepository;
        $this->subeventsApplicationRepository = $subeventsApplicationRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/applications_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \App\Model\Settings\SettingsException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentApplicationsGrid($name)
    {
        $this->user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();
        $userHasFixedFeeRole = $this->user->hasFixedFeeRole();

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);

        if (!$explicitSubeventsExists)
            $qb = $this->rolesApplicationRepository;
        elseif (!$userHasFixedFeeRole)
            $qb = $this->subeventsApplicationRepository;
        else
            $qb = $this->applicationRepository;

        $qb = $qb->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u = :user')
            ->andWhere('a.validTo IS NULL')
            ->setParameter('user', $this->user)
            ->orderBy('a.applicationId');

        $grid->setDataSource($qb);
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('applicationDate', 'web.profile.applications_application_date')
            ->setFormat('j. n. Y H:i');

        if ($userHasFixedFeeRole)
        $grid->addColumnText('roles', 'web.profile.applications_roles', 'rolesText');

        if ($explicitSubeventsExists)
            $grid->addColumnText('subevents', 'web.profile.applications_subevents', 'subeventsText');

        $grid->addColumnNumber('fee', 'web.profile.applications_fee');

        $grid->addColumnText('variable_symbol', 'web.profile.applications_variable_symbol', 'variableSymbolText');

        $grid->addColumnDateTime('maturityDate', 'web.profile.applications_maturity_date')
            ->setFormat('j. n. Y');

        $grid->addColumnText('state', 'web.profile.applications_state')
            ->setRenderer(function (Application $row) {
                return $this->applicationService->getStateText($row);
            });


        if ($explicitSubeventsExists) {
            if ($this->applicationService->isAllowedAddApplication($this->user)) {
                $grid->addInlineAdd()->onControlAdd[] = function ($container) {
                    $options = $this->subeventRepository->getNonRegisteredExplicitOptionsWithCapacity($this->user);
                    $container->addMultiSelect('subevents', '', $options)
                        ->setAttribute('class', 'datagrid-multiselect')
                        ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
                };
                $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
                $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
            }

            $grid->addInlineEdit()->onControlAdd[] = function ($container) {
                $options = $this->subeventRepository->getExplicitOptionsWithCapacity();
                $container->addMultiSelect('subevents', '', $options)
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            };
            $grid->getInlineEdit()->setText($this->translator->translate('web.profile.applications_edit'));
            $grid->getInlineEdit()->onSetDefaults[] = function ($container, SubeventsApplication $item) {
                $container->setDefaults([
                    'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents())
                ]);
            };
            $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];
            $grid->allowRowsInlineEdit(function(Application $item) {
                return $this->applicationService->isAllowedEditApplication($item);
            });
        }


        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', function (Application $item) {
            return $item->getState() == ApplicationState::PAID
                && $item->getPaymentMethod() == PaymentType::BANK
                && $item->getPaymentDate();
        });

        $grid->addAction('cancelApplication', 'web.profile.applications_cancel_application')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('web.profile.applications_cancel_application_confirm')
            ])->setClass('btn btn-xs btn-danger');
        $grid->allowRowsAction('cancelApplication', function (Application $item) {
            return $this->applicationService->isAllowedEditApplication($item);
        });


        $grid->setColumnsSummary(['fee'], function(Application $item, $column) {
            return $item->isCanceled() ? 0 : $item->getFee();
        });
    }

    /**
     * Zpracuje přidání podakcí.
     * @param $values
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function add($values)
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);
        $selectedAndUsersSubevents = clone $this->user->getSubevents();
        foreach ($selectedSubevents as $subevent)
            $selectedAndUsersSubevents->add($subevent);

        $p = $this->getPresenter();

        if (!$this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            if (!$this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
            if (!$this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
                    ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
        }

        $this->applicationService->addSubeventsApplication($this->user, $selectedSubevents, $this->user);

        $p->flashMessage('web.profile.applications_add_subevents_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu přihlášky.
     * @param $id
     * @param $values
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function edit($id, $values)
    {
        $application = $this->applicationRepository->findById($id);

        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);
        $selectedAndUsersSubevents = clone $this->user->getSubevents();
        foreach ($selectedSubevents as $subevent)
            $selectedAndUsersSubevents->add($subevent);
        foreach ($application->getSubevents() as $subevent)
            $selectedAndUsersSubevents->removeElement($subevent);

        $p = $this->getPresenter();

        if (!$this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
            $p->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            if (!$this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
            if (!$this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
                    ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
                );
                $p->flashMessage($message, 'danger');
                $this->redirect('this');
            }
        }

        $this->applicationService->updateSubeventsApplication($application, $selectedSubevents, $this->user);

        $p->flashMessage('web.profile.applications_edit_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function handleGeneratePaymentProofBank($id)
    {
        $this->pdfExportService->generateApplicationsPaymentProof(
            $this->applicationRepository->findById($id), "potvrzeni-o-prijeti-platby.pdf",
            $this->userRepository->findById($this->getPresenter()->getUser()->id)
        );
    }

    /**
     * Zruší přihlášku.
     * @param $id
     * @throws \App\Model\Settings\SettingsException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function handleCancelApplication($id)
    {
        $application = $this->applicationRepository->findById($id);

        if ($this->applicationService->isAllowedEditApplication($application)) {
            $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED, $application->getUser());
            $this->getPresenter()->flashMessage('web.profile.applications_application_canceled', 'success');
        }

        $this->redirect('this');
    }
}

