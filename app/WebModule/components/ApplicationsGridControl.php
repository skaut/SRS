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
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\MailService;
use App\Services\PdfExportService;
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
     */
    public function __construct(Translator $translator, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SubeventRepository $subeventRepository, ApplicationService $applicationService,
                                ProgramRepository $programRepository, MailService $mailService,
                                SettingsRepository $settingsRepository, Authenticator $authenticator,
                                PdfExportService $pdfExportService)
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
     */
    public function createComponentApplicationsGrid($name)
    {
        $this->user = $this->userRepository->findById($this->getPresenter()->getUser()->getId());

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->applicationRepository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.id = ' . $this->user->getId())
        );
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('applicationDate', 'web.profile.applications_application_date')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('roles', 'web.profile.applications_roles')
            ->setRenderer(function ($row) {
                if (!$row->isFirst())
                    return "";

                $roles = [];
                foreach ($row->getUser()->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            });

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $grid->addColumnText('subevents', 'web.profile.applications_subevents')
                ->setRenderer(function ($row) {
                    $subevents = [];
                    foreach ($row->getSubevents() as $subevent) {
                        $subevents[] = $subevent->getName();
                    }
                    return implode(", ", $subevents);
                });
        }

        $grid->addColumnNumber('fee', 'web.profile.applications_fee');

        $grid->addColumnText('variable_symbol', 'web.profile.applications_variable_symbol');

        $grid->addColumnDateTime('maturityDate', 'web.profile.applications_maturity_date')
            ->setFormat('j. n. Y');

        $grid->addColumnText('state', 'web.profile.applications_state')
            ->setRenderer(function ($row) {
                $state = $this->translator->translate('common.application_state.' . $row->getState());

                if ($row->getState() == ApplicationState::PAID && $row->getPaymentDate() !== NULL)
                    $state .= ' (' . $row->getPaymentDate()->format('j. n. Y') . ')';

                return $state;
            });

        if ($this->applicationService->isAllowedAddSubevents($this->user)) {
            $grid->addInlineAdd()->onControlAdd[] = function ($container) {
                $subeventsSelect = $container->addMultiSelect('subevents', '', $this->subeventRepository->getNonRegisteredExplicitOptionsWithCapacity($this->user))
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            };
            $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
            $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
        }

        if ($this->applicationService->isAllowedEditFirstApplication($this->user)) {
            $grid->addInlineEdit()->onControlAdd[] = function ($container) {
                $rolesSelect = $container->addMultiSelect('roles', '', $this->roleRepository->getRegisterableNowOrUsersOptionsWithCapacity($this->user))
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_roles_empty');

                if ($this->subeventRepository->explicitSubeventsExists()) {
                    if ($this->user->getSubevents()->contains($this->subeventRepository->findImplicit()))
                        $options = $this->subeventRepository->getSubeventsOptionsWithCapacity();
                    else
                        $options = $this->subeventRepository->getExplicitOptionsWithCapacity();

                    $subeventsSelect = $container->addMultiSelect('subevents', '', $options)
                        ->setAttribute('class', 'datagrid-multiselect');
                }
            };
            $grid->getInlineEdit()->setText($this->translator->translate('web.profile.applications_edit'));
            $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
                $container->setDefaults([
                    'roles' => $this->roleRepository->findRolesIds($item->getUser()->getRoles()),
                    'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents())
                ]);
            };
            $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];
        }

        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', function ($item) {
            return $item->getState() == ApplicationState::PAID
                && $item->getPaymentMethod() == PaymentType::BANK
                && $item->getPaymentDate();
        });

        $grid->setColumnsSummary(['fee']);
    }

    /**
     * Zpracuje přidání podakcí.
     * @param $values
     */
    public function add($values)
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);
        $selectedAndUsersSubevents = $this->user->getSubevents();
        foreach ($selectedSubevents as $subevent)
            $selectedAndUsersSubevents->add($subevent);

        //kontrola podakci
        if (!$this->validateSubeventsCapacities($selectedSubevents)) {
            $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            $incompatibleSubevents = $subevent->getIncompatibleSubevents();
            if (count($incompatibleSubevents) > 0 && !$this->validateSubeventsIncompatible($selectedAndUsersSubevents, $subevent)) {
                $messageThis = $subevent->getName();

                $incompatibleSubeventsNames = [];
                foreach ($incompatibleSubevents as $incompatibleSubevent) {
                    $incompatibleSubeventsNames[] = $incompatibleSubevent->getName();
                }
                $messageOthers = implode(', ', $incompatibleSubeventsNames);

                $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
                    ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
                );
                $this->getPresenter()->flashMessage($message, 'danger');
                $this->redirect('this');
            }

            $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
            if (count($requiredSubevents) > 0 && !$this->validateSubeventsRequired($selectedAndUsersSubevents, $subevent)) {
                $messageThis = $subevent->getName();

                $requiredSubeventsNames = [];
                foreach ($requiredSubevents as $requiredSubevent) {
                    $requiredSubeventsNames[] = $requiredSubevent->getName();
                }
                $messageOthers = implode(', ', $requiredSubeventsNames);

                $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
                    ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
                );
                $this->getPresenter()->flashMessage($message, 'danger');
                $this->redirect('this');
            }
        }

        //zpracovani zmen
        $application = new Application();
        $fee = $this->applicationService->countFee($this->user->getRoles(), $selectedSubevents, FALSE);
        $application->setUser($this->user);
        $application->setSubevents($selectedSubevents);
        $application->setApplicationDate(new \DateTime());
        $application->setApplicationOrder($this->applicationRepository->findLastApplicationOrder() + 1);
        $application->setMaturityDate($this->applicationService->countMaturityDate());
        $application->setVariableSymbol($this->applicationService->generateVariableSymbol($this->user));
        $application->setFee($fee);
        $application->setState($fee == 0 ? ApplicationState::PAID : ApplicationState::WAITING_FOR_PAYMENT);
        $application->setFirst(FALSE);
        $this->applicationRepository->save($application);

        $this->user->addApplication($application);
        $this->userRepository->save($this->user);

        $this->programRepository->updateUserPrograms($this->user);
        $this->userRepository->save($this->user);

        //zaslani potvrzovaciho e-mailu
        $subeventsNames = [];
        foreach ($this->user->getSubevents() as $subevent) {
            $subeventsNames[] = $subevent->getName();
        }

        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::SUBEVENT_ADDED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => implode(', ', $subeventsNames)
        ]);

        $this->getPresenter()->flashMessage('web.profile.applications_add_subevents_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu přihlášky.
     * @param $id
     * @param $values
     */
    public function edit($id, $values)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);

        //kontrola roli
        if (!$this->validateRolesCapacities($selectedRoles)) {
            $this->getPresenter()->flashMessage('web.profile.applications_roles_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        if (!$this->validateRolesRegisterable($selectedRoles)) {
            $this->getPresenter()->flashMessage('web.profile.applications_role_is_not_registerable', 'danger');
            $this->redirect('this');
        }

        foreach ($this->roleRepository->findAllRegisterableNowOrUsersOrderedByName($this->user) as $role) {
            $incompatibleRoles = $role->getIncompatibleRoles();
            if (count($incompatibleRoles) > 0 && !$this->validateRolesIncompatible($selectedRoles, $role)) {
                $messageThis = $role->getName();

                $incompatibleRolesNames = [];
                foreach ($incompatibleRoles as $incompatibleRole) {
                    $incompatibleRolesNames[] = $incompatibleRole->getName();
                }
                $messageOthers = implode(', ', $incompatibleRolesNames);

                $message = $this->translator->translate('web.profile.applications_incompatible_roles_selected', NULL,
                    ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]
                );
                $this->getPresenter()->flashMessage($message, 'danger');
                $this->redirect('this');
            }

            $requiredRoles = $role->getRequiredRolesTransitive();
            if (count($requiredRoles) > 0 && !$this->validateRolesRequired($selectedRoles, $role)) {
                $messageThis = $role->getName();

                $requiredRolesNames = [];
                foreach ($requiredRoles as $requiredRole) {
                    $requiredRolesNames[] = $requiredRole->getName();
                }
                $messageOthers = implode(', ', $requiredRolesNames);

                $message = $this->translator->translate('web.profile.applications_required_roles_not_selected', NULL,
                    ['role' => $messageThis, 'requiredRoles' => $messageOthers]
                );
                $this->getPresenter()->flashMessage($message, 'danger');
                $this->redirect('this');
            }
        }


        if ($this->subeventRepository->explicitSubeventsExists()) {
            $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);

            //kontrola podakci
            if (!$this->validateSubeventsCapacities($selectedSubevents)) {
                $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
                $this->redirect('this');
            }

            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
                $incompatibleSubevents = $subevent->getIncompatibleSubevents();
                if (count($incompatibleSubevents) > 0 && !$this->validateSubeventsIncompatible($selectedSubevents, $subevent)) {
                    $messageThis = $subevent->getName();

                    $incompatibleSubeventsNames = [];
                    foreach ($incompatibleSubevents as $incompatibleSubevent) {
                        $incompatibleSubeventsNames[] = $incompatibleSubevent->getName();
                    }
                    $messageOthers = implode(', ', $incompatibleSubeventsNames);

                    $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
                        ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
                    );
                    $this->getPresenter()->flashMessage($message, 'danger');
                    $this->redirect('this');
                }

                $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
                if (count($requiredSubevents) > 0 && !$this->validateSubeventsRequired($selectedSubevents, $subevent)) {
                    $messageThis = $subevent->getName();

                    $requiredSubeventsNames = [];
                    foreach ($requiredSubevents as $requiredSubevent) {
                        $requiredSubeventsNames[] = $requiredSubevent->getName();
                    }
                    $messageOthers = implode(', ', $requiredSubeventsNames);

                    $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
                        ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
                    );
                    $this->getPresenter()->flashMessage($message, 'danger');
                    $this->redirect('this');
                }
            }
        }

        //pokud si uživatel přidá roli, která vyžaduje schválení, stane se neschválený
        $approved = TRUE;
        if ($approved) {
            foreach ($selectedRoles as $role) {
                if (!$role->isApprovedAfterRegistration() && !$this->user->getRoles()->contains($role)) {
                    $approved = FALSE;
                    break;
                }
            }
        }

        //zpracovani zmen
        $this->user->setRoles($selectedRoles);
        $this->user->setApproved($approved);
        $this->userRepository->save($this->user);

        $fee = $this->applicationService->countFee($selectedRoles, $selectedSubevents);
        $application = $this->applicationRepository->findById($id);

        if ($this->subeventRepository->explicitSubeventsExists() && !empty($values['subevents']))
            $application->setSubevents($selectedSubevents);
        else
            $application->setSubevents(new ArrayCollection([$this->subeventRepository->findImplicit()]));

        $application->setFee($fee);
        $application->setState($fee == 0 || $application->getPaymentDate()
            ? ApplicationState::PAID
            : ApplicationState::WAITING_FOR_PAYMENT);
        $this->applicationRepository->save($application);

        $this->programRepository->updateUserPrograms($this->user);
        $this->userRepository->save($this->user);

        //zaslani potvrzovaciho e-mailu
        $rolesNames = [];
        foreach ($this->user->getRoles() as $role) {
            $rolesNames[] = $role->getName();
        }

        $subeventsNames = [];
        foreach ($this->user->getSubevents() as $subevent) {
            $subeventsNames[] = $subevent->getName();
        }

        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::REGISTRATION_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_ROLES => implode(', ', $rolesNames),
            TemplateVariable::USERS_SUBEVENTS => implode(', ', $subeventsNames)
        ]);

        $this->authenticator->updateRoles($this->getPresenter()->getUser());

        $this->getPresenter()->flashMessage('web.profile.applications_edit_successful', 'success');
        $this->redirect('this');
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
     * Ověří kapacitu rolí.
     * @param $selectedRoles
     * @return bool
     */
    public function validateRolesCapacities($selectedRoles)
    {
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity()) {
                if ($this->roleRepository->countUnoccupiedInRole($role) < 1 && !$this->user->isInRole($role))
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kompatibilitu rolí.
     * @param $selectedRoles
     * @param $testRole
     * @return bool
     */
    public function validateRolesIncompatible($selectedRoles, Role $testRole)
    {
        if (!$selectedRoles->contains($testRole))
            return TRUE;

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if ($selectedRoles->contains($incompatibleRole))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných rolí.
     * @param $selectedRoles
     * @param $testRole
     * @return bool
     */
    public function validateRolesRequired($selectedRoles, Role $testRole)
    {
        if (!$selectedRoles->contains($testRole))
            return TRUE;

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (!$selectedRoles->contains($requiredRole))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří registrovatelnost rolí.
     * @param $selectedRoles
     * @return bool
     */
    public function validateRolesRegisterable($selectedRoles)
    {
        foreach ($selectedRoles as $role) {
            if (!$role->isRegisterableNow() && !$this->user->isInRole($role))
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Ověří kapacitu podakcí.
     * @param $selectedSubevents
     * @return bool
     */
    public function validateSubeventsCapacities($selectedSubevents)
    {
        foreach ($selectedSubevents as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                if ($this->subeventRepository->countUnoccupiedInSubevent($subevent) < 1 && !$this->user->hasSubevent($subevent))
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param $selectedSubevents
     * @param Subevent $testSubevent
     * @return bool
     */
    public function validateSubeventsIncompatible($selectedSubevents, Subevent $testSubevent)
    {
        if (!$selectedSubevents->contains($testSubevent))
            return TRUE;

        foreach ($testSubevent->getIncompatibleSubevents() as $incompatibleSubevent) {
            if ($selectedSubevents->contains($incompatibleSubevent))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných podakcí.
     * @param $selectedSubevents
     * @param Subevent $testSubevent
     * @return bool
     */
    public function validateSubeventsRequired($selectedSubevents, Subevent $testSubevent)
    {
        if (!$selectedSubevents->contains($testSubevent))
            return TRUE;

        foreach ($testSubevent->getRequiredSubeventsTransitive() as $requiredSubevent) {
            if (!$selectedSubevents->contains($requiredSubevent))
                return FALSE;
        }

        return TRUE;
    }
}
