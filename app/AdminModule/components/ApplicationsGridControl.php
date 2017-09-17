<?php

namespace App\AdminModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\Authenticator;
use App\Services\MailService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu přihlášek.
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
     */
    public function __construct(Translator $translator, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, RoleRepository $roleRepository,
                                SubeventRepository $subeventRepository, ApplicationService $applicationService,
                                ProgramRepository $programRepository, MailService $mailService,
                                SettingsRepository $settingsRepository, Authenticator $authenticator)
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
        $this->user = $this->userRepository->findById($this->getPresenter()->getParameter('id'));

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

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method');

        $grid->addColumnDateTime('paymentDate', 'admin.users.users_payment_date');

        $grid->addColumnDateTime('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date');

        $grid->addColumnText('state', 'web.profile.applications_state')
            ->setRenderer(function ($row) {
                $state = $this->translator->translate('common.application_state.' . $row->getState());

                if ($row->getState() == ApplicationState::PAID && $row->getPaymentDate() !== NULL)
                    $state .= ' (' . $row->getPaymentDate()->format('j. n. Y') . ')';

                return $state;
            });

        //TODO editace VS, platebni metoda, datum zaplaceni, datum vytisteni dokladu, mail pri potvrzeni platby
//        if ($values['paymentDate'] !== NULL && $oldPaymentDate === NULL) {
//            $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::PAYMENT_CONFIRMED, [
//                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
//            ]);
//        }

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $grid->addInlineAdd()->onControlAdd[] = function ($container) {
                $subeventsSelect = $container->addMultiSelect('subevents', '', $this->subeventRepository->getNonRegisteredExplicitOptionsWithCapacity($this->user))
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            };
            $grid->getInlineAdd()->setText($this->translator->translate('web.profile.applications_add_subevents'));
            $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
        }

        $grid->addInlineEdit()->onControlAdd[] = function ($container) {
            $rolesSelect = $container->addMultiSelect('roles', '', $this->roleRepository->getRegisterableNowOrUsersOptionsWithCapacity($this->user))
                ->setAttribute('class', 'datagrid-multiselect');

            if ($this->subeventRepository->explicitSubeventsExists()) {
                $subeventsSelect = $container->addMultiSelect('subevents', '', $this->subeventRepository->getExplicitOptionsWithCapacity())
                    ->setAttribute('class', 'datagrid-multiselect')
                    ->addRule(Form::FILLED, 'web.profile.applications_subevents_empty');
            }

            $container->addText('variableSymbol', 'admin.users.users_variable_symbol')
                ->addRule(Form::FILLED)
                ->addRule(Form::PATTERN, 'admin.users.users_edit_variable_symbol_format', '^\d{1,10}$');

            $container->addSelect('paymentMethod', 'admin.users.users_payment_method', $this->preparePaymentMethodOptions());

            $container->addDatePicker('paymentDate', 'admin.users.users_payment_date');

            $container->addDatePicker('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container->setDefaults([
                'roles' => $this->roleRepository->findRolesIds($item->getUser()->getRoles()),
                'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents())
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('generatePaymentProofBank', 'web.profile.applications_download_payment_proof');
        $grid->allowRowsAction('generatePaymentProofBank', function ($item) {
            return $item->getPaymentMethod() == PaymentType::BANK;
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
        if (!$this->checkSubeventsCapacities($selectedSubevents)) {
            $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
            $this->redirect('this');
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

        $this->programRepository->updateUserPrograms($this->user);
        $this->userRepository->save($this->user);

//        $rolesNames = "";
//        $first = TRUE;
//        foreach ($this->user->getRoles() as $role) {
//            if ($first) {
//                $rolesNames = $role->getName();
//                $first = FALSE;
//            }
//            else {
//                $rolesNames .= ', ' . $role->getName();
//            }
//        }

        //TODO mail vcetne podakci
//        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::ROLE_CHANGED, [
//            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
//            TemplateVariable::USERS_ROLES => $rolesNames
//        ]);

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
        if (!$this->checkRolesCapacities($selectedRoles)) {
            $this->getPresenter()->flashMessage('web.profile.applications_roles_capacity_occupied', 'danger');
            $this->redirect('this');
        }

        if (!$this->checkRolesRegisterable($selectedRoles)) {
            $this->getPresenter()->flashMessage('web.profile.applications_role_is_not_registerable', 'danger');
            $this->redirect('this');
        }

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);

            //kontrola podakci
            if (!$this->checkSubeventsCapacities($selectedSubevents)) {
                $this->getPresenter()->flashMessage('web.profile.applications_subevents_capacity_occupied', 'danger');
                $this->redirect('this');
            }

            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
                $incompatibleSubevents = $subevent->getIncompatibleSubevents();
                if (count($incompatibleSubevents) > 0 && !$this->checkSubeventsIncompatible($selectedSubevents, $subevent)) {
                    $messageThis = $subevent->getName();

                    $first = TRUE;
                    $messageOthers = "";
                    foreach ($incompatibleSubevents as $incompatibleSubevent) {
                        if ($first)
                            $messageOthers .= $incompatibleSubevent->getName();
                        else
                            $messageOthers .= ", " . $incompatibleSubevent->getName();

                        $first = FALSE;
                    }

                    $message = $this->translator->translate('web.profile.applications_incompatible_subevents_selected', NULL,
                        ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
                    );
                    $this->getPresenter()->flashMessage($message, 'danger');
                    $this->redirect('this');
                }

                $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
                if (count($requiredSubevents) > 0 && !$this->checkSubeventsRequired($selectedSubevents, $subevent)) {
                    $messageThis = $subevent->getName();

                    $first = TRUE;
                    $messageOthers = "";
                    foreach ($requiredSubevents as $requiredSubevent) {
                        if ($first)
                            $messageOthers .= $requiredSubevent->getName();
                        else
                            $messageOthers .= ", " . $requiredSubevent->getName();
                        $first = FALSE;
                    }

                    $message = $this->translator->translate('web.profile.applications_required_subevents_not_selected', NULL,
                        ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
                    );
                    $this->getPresenter()->flashMessage($message, 'danger');
                    $this->redirect('this');
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
        }


        //zpracovani zmen
        $this->user->setRoles($selectedRoles);
        $this->user->setApproved($approved);
        $this->userRepository->save($this->user);

        $fee = $this->applicationService->countFee($selectedRoles, $selectedSubevents);
        $application = $this->applicationRepository->findById($id);
        if ($this->subeventRepository->explicitSubeventsExists())
            $application->setSubevents($selectedSubevents);
        $application->setFee($fee);
        $application->setState($fee == 0 ? ApplicationState::PAID : ApplicationState::WAITING_FOR_PAYMENT);
        $this->applicationRepository->save($application);

        $this->programRepository->updateUserPrograms($this->user);
        $this->userRepository->save($this->user);

//        $rolesNames = "";
//        $first = TRUE;
//        foreach ($this->user->getRoles() as $role) {
//            if ($first) {
//                $rolesNames = $role->getName();
//                $first = FALSE;
//            }
//            else {
//                $rolesNames .= ', ' . $role->getName();
//            }
//        }

        //TODO mail vcetne podakci
//        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::ROLE_CHANGED, [
//            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
//            TemplateVariable::USERS_ROLES => $rolesNames
//        ]);

        $this->authenticator->updateRoles($this->getPresenter()->getUser());

        $this->getPresenter()->flashMessage('web.profile.applications_edit_successful', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     */
    public function handleGeneratePaymentProofBank($id)
    {
        //TODO generovani potvrzeni o zaplaceni
//        if (!$this->user->getIncomeProofPrintedDate()) {
//            $this->user->setIncomeProofPrintedDate(new \DateTime());
//            $this->userRepository->save($user);
//        }
//        $this->pdfExportService->generatePaymentProof($user, "potvrzeni-o-prijeti-platby.pdf");
    }

    /**
     * Ověří obsazenost podakcí.
     * @param $selectedSubevents
     * @return bool
     */
    public function validateSubeventsCapacities($selectedSubevents)
    {
        if ($this->user->isApproved()) {
            foreach ($selectedSubevents as $subevent) {
                if ($subevent->hasLimitedCapacity()) {
                    if ($this->subeventRepository->countUnoccupiedInSubevent($subevent) < 1 && !$this->user->hasSubevent($subevent))
                        return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Ověří obsazenost rolí.
     * @param $selectedRoles
     * @return bool
     */
    public function validateRolesCapacities($selectedRoles)
    {
        if ($this->user->isApproved()) {
            foreach ($selectedRoles as $role) {
                if ($role->hasLimitedCapacity()) {
                    if ($this->roleRepository->countUnoccupiedInRole($role) < 1 && !$this->user->isInRole($role))
                        return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Ověří kombinaci role "Neregistrovaný" s ostatními rolemi.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateRolesCombination($selectedRoles)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($field->getValue());
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);

        if ($selectedRoles->contains($nonregisteredRole) && $selectedRoles->count() > 1)
            return FALSE;

        return TRUE;
    }

    public function validateAdditionalApplicationRoles($selectedRoles, $first) {
        //TODO
    }

    /**
     * Vrátí platební metody jako možnosti pro select.
     * @return array
     */
    private function preparePaymentMethodOptions()
    {
        $options = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }
}


