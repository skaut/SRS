<?php

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplicationRepository;
use App\Model\User\SubeventsApplication;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\Services\ProgramService;
use App\Services\UserService;
use App\Utils\Validators;
use Doctrine\Common\Collections\ArrayCollection;
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
     */
    public function __construct(Translator $translator, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SubeventRepository $subeventRepository, ApplicationService $applicationService,
                                ProgramRepository $programRepository, MailService $mailService,
                                SettingsRepository $settingsRepository, Authenticator $authenticator,
                                PdfExportService $pdfExportService, ProgramService $programService,
                                UserService $userService, Validators $validators,
                                RolesApplicationRepository $rolesApplicationRepository)
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
                return $item->getType() == Application::SUBEVENTS;
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
            return $item->getType() == Application::SUBEVENTS;
        });

        $grid->setColumnsSummary(['fee'], function(Application $item, $column) {
            return $item->isCanceled() ? 0 : $item->getFee();
        });
    }

    /**
     * Zpracuje přidání podakcí.
     * @param $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function add($values)
    {
//        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);
//        $selectedAndUsersSubevents = $this->user->getSubevents();
//        foreach ($selectedSubevents as $subevent)
//            $selectedAndUsersSubevents->add($subevent);
//
//        //kontrola podakci
//        if (!$this->validators->validateSubeventsEmpty($selectedSubevents)) {
//            $this->getPresenter()->flashMessage('web.profile.applications_subevents_empty', 'danger');
//            $this->redirect('this');
//        }
//
//        if (!$this->validators->validateSubeventsCapacities($selectedSubevents, $this->user)) {
//            $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
//            $this->redirect('this');
//        }
//
//        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
//            if (!$this->validators->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
//                $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
//                    ['subevent' => $subevent->getName(), 'incompatibleSubevents' => $subevent->getIncompatibleSubeventsText()]
//                );
//                $this->getPresenter()->flashMessage($message, 'danger');
//                $this->redirect('this');
//            }
//            if (!$this->validators->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
//                $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
//                    ['subevent' => $subevent->getName(), 'requiredSubevents' => $subevent->getRequiredSubeventsTransitiveText()]
//                );
//                $this->getPresenter()->flashMessage($message, 'danger');
//                $this->redirect('this');
//            }
//        }
//
//        //zpracovani zmen
//        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($selectedSubevents) {
//            $application = new Application();
//            $fee = $this->applicationService->countFee($this->user->getRoles(), $selectedSubevents, FALSE);
//            $application->setUser($this->user);
//            $application->setSubevents($selectedSubevents);
//            $application->setApplicationDate(new \DateTime());
//            $application->setMaturityDate($this->applicationService->countMaturityDate());
//            $application->setFee($fee);
//            $application->setState($fee == 0 ? ApplicationState::PAID : ApplicationState::WAITING_FOR_PAYMENT);
//            $application->setFirst(FALSE);
//            $this->applicationRepository->save($application);
//
//            $application->setVariableSymbol($this->applicationService->generateVariableSymbol($application));
//            $this->applicationRepository->save($application);
//
//            $this->user->addApplication($application);
//            $this->userRepository->save($this->user);
//
//            $this->programService->updateUserPrograms($this->user);
//
//            //zaslani potvrzovaciho e-mailu
//            $this->mailService->sendMailFromTemplate($this->user, '', Template::SUBEVENT_ADDED, [
//                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
//                TemplateVariable::USERS_SUBEVENTS => $this->user->getSubeventsText()
//            ]);
//        });
//
//        $this->getPresenter()->flashMessage('web.profile.applications_add_subevents_successful', 'success');
//        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu přihlášky.
     * @param $id
     * @param $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function edit($id, $values)
    {
//        $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);
//
//        //kontrola roli
//        if (!$this->validateRolesCapacities($selectedRoles)) {
//            $this->getPresenter()->flashMessage('web.profile.applications_roles_capacity_occupied', 'danger');
//            $this->redirect('this');
//        }
//
//        if (!$this->validateRolesRegisterable($selectedRoles)) {
//            $this->getPresenter()->flashMessage('web.profile.applications_role_is_not_registerable', 'danger');
//            $this->redirect('this');
//        }
//
//        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
//            $incompatibleRoles = $role->getIncompatibleRoles();
//            if (count($incompatibleRoles) > 0 && !$this->validateRolesIncompatible($selectedRoles, $role)) {
//                $messageThis = $role->getName();
//
//                $incompatibleRolesNames = [];
//                foreach ($incompatibleRoles as $incompatibleRole) {
//                    $incompatibleRolesNames[] = $incompatibleRole->getName();
//                }
//                $messageOthers = implode(', ', $incompatibleRolesNames);
//
//                $message = $this->translator->translate('web.profile.applications_incompatible_roles_selected', NULL,
//                    ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
//                );
//                $this->getPresenter()->flashMessage($message, 'danger');
//                $this->redirect('this');
//            }
//
//            $requiredRoles = $role->getRequiredRolesTransitive();
//            if (count($requiredRoles) > 0 && !$this->validateRolesRequired($selectedRoles, $role)) {
//                $messageThis = $role->getName();
//
//                $requiredRolesNames = [];
//                foreach ($requiredRoles as $requiredRole) {
//                    $requiredRolesNames[] = $requiredRole->getName();
//                }
//                $messageOthers = implode(', ', $requiredRolesNames);
//
//                $message = $this->translator->translate('web.profile.applications_required_roles_not_selected', NULL,
//                    ['role' => $messageThis, 'requiredRoles' => $messageOthers]
//                );
//                $this->getPresenter()->flashMessage($message, 'danger');
//                $this->redirect('this');
//            }
//        }
//
//
//        if ($this->subeventRepository->explicitSubeventsExists()) {
//            $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);
//
//            //kontrola podakci
//            if (!$this->validateSubeventsEmpty($selectedSubevents)) {
//                $this->getPresenter()->flashMessage('web.profile.applications_subevents_empty', 'danger');
//                $this->redirect('this');
//            }
//
//            if (!$this->validateSubeventsCapacities($selectedSubevents)) {
//                $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
//                $this->redirect('this');
//            }
//
//            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
//                $incompatibleSubevents = $subevent->getIncompatibleSubevents();
//                if (count($incompatibleSubevents) > 0 && !$this->validateSubeventsIncompatible($selectedSubevents, $subevent)) {
//                    $messageThis = $subevent->getName();
//
//                    $incompatibleSubeventsNames = [];
//                    foreach ($incompatibleSubevents as $incompatibleSubevent) {
//                        $incompatibleSubeventsNames[] = $incompatibleSubevent->getName();
//                    }
//                    $messageOthers = implode(', ', $incompatibleSubeventsNames);
//
//                    $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
//                        ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
//                    );
//                    $this->getPresenter()->flashMessage($message, 'danger');
//                    $this->redirect('this');
//                }
//
//                $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
//                if (count($requiredSubevents) > 0 && !$this->validateSubeventsRequired($selectedSubevents, $subevent)) {
//                    $messageThis = $subevent->getName();
//
//                    $requiredSubeventsNames = [];
//                    foreach ($requiredSubevents as $requiredSubevent) {
//                        $requiredSubeventsNames[] = $requiredSubevent->getName();
//                    }
//                    $messageOthers = implode(', ', $requiredSubeventsNames);
//
//                    $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
//                        ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
//                    );
//                    $this->getPresenter()->flashMessage($message, 'danger');
//                    $this->redirect('this');
//                }
//            }
//        }
//
//        //zpracovani zmen
//        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($id, $selectedRoles, $selectedSubevents, $values) {
//            $application = $this->applicationRepository->findById($id);
//
//            if ($this->subeventRepository->explicitSubeventsExists() && !empty($values['subevents']))
//                $application->setSubevents($selectedSubevents);
//            else
//                $application->setSubevents(new ArrayCollection([$this->subeventRepository->findImplicit()]));
//
//            $this->userService->changeRoles($this->user, $selectedRoles);
//
//            $this->programService->updateUserPrograms($this->user);
//
//            //zaslani potvrzovaciho e-mailu
//            $subeventsNames = [];
//            foreach ($this->user->getSubevents() as $subevent) {
//                $subeventsNames[] = $subevent->getName();
//            }
//
//            $this->mailService->sendMailFromTemplate($this->user, '', Template::SUBEVENTS_CHANGED, [
//                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
//                TemplateVariable::USERS_SUBEVENTS => implode(', ', $subeventsNames)
//            ]);
//
//            $this->authenticator->updateRoles($this->getPresenter()->getUser());
//        });
//
//        $this->getPresenter()->flashMessage('web.profile.applications_edit_successful', 'success');
//        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     * @param $id
     */
    public function handleGeneratePaymentProofBank($id)
    {
        $this->pdfExportService->generateApplicationsPaymentProof(
            $this->applicationRepository->findById($id),
            "potvrzeni-o-prijeti-platby.pdf"
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
//        $application = $this->applicationRepository->findById($id);
//
//        if ($this->applicationService->isAllowedCancelApplication($application)) {
//            $user = $application->getUser();
//
//            $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($application, $user) {
//                $application->setState(ApplicationState::CANCELED);
//                $this->applicationRepository->save($application);
//
//                $this->programService->updateUserPrograms($user);
//            });
//
//            $this->getPresenter()->flashMessage('web.profile.applications_application_canceled', 'success');
//        }
//
//        $this->redirect('this');
    }
}

