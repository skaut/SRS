<?php

namespace App\AdminModule\Components;

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
        $this->user = $this->userRepository->findById($this->getPresenter()->getParameter('id'));

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->applicationRepository->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.id = ' . $this->user->getId())
        );
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('applicationDate', 'admin.users.users_applications_application_date')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('roles', 'admin.users.users_applications_roles')
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
            $grid->addColumnText('subevents', 'admin.users.users_applications_subevents', 'subeventsText');
        }

        $grid->addColumnNumber('fee', 'admin.users.users_applications_fee');

        $grid->addColumnText('variableSymbol', 'admin.users.users_applications_variable_symbol');

        $grid->addColumnDateTime('maturityDate', 'admin.users.users_applications_maturity_date')
            ->setFormat('j. n. Y');

        $grid->addColumnText('paymentMethod', 'admin.users.users_applications_payment_method')
            ->setRenderer(function ($row) {
                $paymentMethod = $row->getPaymentMethod();
                if ($paymentMethod)
                    return $this->translator->translate('common.payment.' . $paymentMethod);
                return NULL;
            });

        $grid->addColumnDateTime('paymentDate', 'admin.users.users_applications_payment_date');

        $grid->addColumnDateTime('incomeProofPrintedDate', 'admin.users.users_applications_income_proof_printed_date');

        $grid->addColumnText('state', 'admin.users.users_applications_state')
            ->setRenderer(function ($row) {
                return $this->translator->translate('common.application_state.' . $row->getState());
            });

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $grid->addInlineAdd()->onControlAdd[] = function ($container) {
                $rolesSelect = $container->addMultiSelect('roles', '', $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
                    ->setAttribute('class', 'datagrid-multiselect');

                if ($this->subeventRepository->explicitSubeventsExists()) {
                    $subeventsSelect = $container->addMultiSelect('subevents', '', $this->subeventRepository->getNonRegisteredSubeventsOptionsWithCapacity($this->user))
                        ->setAttribute('class', 'datagrid-multiselect');
                }
            };
            $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
        }

        $grid->addInlineEdit()->onControlAdd[] = function ($container) {
            $rolesSelect = $container->addMultiSelect('roles', '', $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
                ->setAttribute('class', 'datagrid-multiselect');

            if ($this->subeventRepository->explicitSubeventsExists()) {
                $subeventsSelect = $container->addMultiSelect('subevents', '', $this->subeventRepository->getSubeventsOptionsWithCapacity())
                    ->setAttribute('class', 'datagrid-multiselect');
            }

            $container->addText('variableSymbol', 'admin.users.users_variable_symbol')
                ->addRule(Form::FILLED, 'admin.users.users_applications_variable_symbol_empty')
                ->addRule(Form::PATTERN, 'admin.users.users_edit_variable_symbol_format', '^\d{1,10}$');

            $container->addSelect('paymentMethod', 'admin.users.users_payment_method', $this->preparePaymentMethodOptions());

            $container->addDatePicker('paymentDate', 'admin.users.users_payment_date');

            $container->addDatePicker('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date');

            $container->addDatePicker('maturityDate', 'admin.users.users_maturity_date');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container->setDefaults([
                'roles' => $item->isFirst() ? $this->roleRepository->findRolesIds($item->getUser()->getRoles()) : NULL,
                'subevents' => $this->subeventRepository->findSubeventsIds($item->getSubevents()),
                'variableSymbol' => $item->getVariableSymbol(),
                'paymentMethod' => $item->getPaymentMethod(),
                'paymentDate' => $item->getPaymentDate(),
                'incomeProofPrintedDate' => $item->getIncomeProofPrintedDate(),
                'maturityDate' => $item->getMaturityDate()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('generatePaymentProofCash', 'admin.users.users_applications_download_payment_proof_cash');
        $grid->allowRowsAction('generatePaymentProofCash', function ($item) {
            return $item->getState() == ApplicationState::PAID
                &&$item->getPaymentMethod() == PaymentType::CASH
                && $item->getPaymentDate();
        });

        $grid->addAction('generatePaymentProofBank', 'admin.users.users_applications_download_payment_proof_bank');
        $grid->allowRowsAction('generatePaymentProofBank', function ($item) {
            return $item->getState() == ApplicationState::PAID
                && $item->getPaymentMethod() == PaymentType::BANK
                && $item->getPaymentDate();
        });

        $grid->addAction('cancelRegistration', 'admin.users.users_applications_cancel_registration')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_applications_cancel_registration_confirm')
            ]);
        $grid->allowRowsAction('cancelRegistration', function ($item) {
            return $item->isFirst();
        });

        $grid->addAction('cancelApplication', 'admin.users.users_applications_cancel_application')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_applications_cancel_application_confirm')
            ]);
        $grid->allowRowsAction('cancelApplication', function ($item) {
            return !$item->isFirst()
                && ($item->getState() == ApplicationState::PAID || $item->getState() == ApplicationState::WAITING_FOR_PAYMENT);
        });

        $grid->setColumnsSummary(['fee']);
    }

    /**
     * Zpracuje přidání podakcí.
     * @param $values
     */
    public function add($values)
    {
        $selectedRoles = $this->roleRepository->findRolesByIds($values['roles']);

        //kontrola roli
        if ($this->user->getApplications()->isEmpty()) {
            if (!$this->validateRolesEmpty($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_empty', 'danger');
                $this->redirect('this');
            }

            if (!$this->validateRolesCapacities($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_occupied', 'danger');
                $this->redirect('this');
            }
        }
        else {
            if ($this->validateRolesEmpty($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_not_empty', 'danger');
                $this->redirect('this');
            }
        }

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);

            //kontrola podakci
            if (!$this->validateSubeventsEmpty($selectedSubevents)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_subevents_empty', 'danger');
                $this->redirect('this');
            }

            if (!$this->validateSubeventsCapacities($selectedSubevents)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
                $this->redirect('this');
            }
        }


        //zpracovani zmen
        $this->applicationRepository->getEntityManager()->transactional(function($em) use($selectedRoles, $selectedSubevents) {
            $application = new Application();

            if ($this->user->getApplications()->isEmpty()) {
                $this->user->setRoles($selectedRoles);
                $this->userRepository->save($this->user);
                $application->setFirst(TRUE);
            } else {
                $application->setFirst(FALSE);
            }

            $fee = $this->applicationService->countFee($selectedRoles, $selectedSubevents);

            $application->setUser($this->user);
            if ($this->subeventRepository->explicitSubeventsExists())
                $application->setSubevents($selectedSubevents);
            else
                $application->setSubevents(new ArrayCollection([$this->subeventRepository->findImplicit()]));
            $application->setApplicationDate(new \DateTime());
            $application->setApplicationOrder($this->applicationRepository->findLastApplicationOrder() + 1);
            $application->setMaturityDate($this->applicationService->countMaturityDate());
            $application->setVariableSymbol($this->applicationService->generateVariableSymbol($this->user));
            $application->setFee($fee);
            $application->setState($fee == 0 ? ApplicationState::PAID : ApplicationState::WAITING_FOR_PAYMENT);
            $this->applicationRepository->save($application);

            $this->programRepository->updateUserPrograms($this->user);
            $this->userRepository->save($this->user);
        });

        $this->getPresenter()->flashMessage('admin.users.users_applications_saved', 'success');
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

        $application = $this->applicationRepository->findById($id);

        //kontrola roli
        if ($application->isFirst()) {
            if (!$this->validateRolesEmpty($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_empty', 'danger');
                $this->redirect('this');
            }

            if (!$this->validateRolesCapacities($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_occupied', 'danger');
                $this->redirect('this');
            }
        }
        else {
            if ($this->validateRolesEmpty($selectedRoles)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_roles_not_empty', 'danger');
                $this->redirect('this');
            }
        }

        if ($this->subeventRepository->explicitSubeventsExists()) {
            $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);

            //kontrola podakci
            if (!$this->validateSubeventsEmpty($selectedSubevents)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_subevents_empty', 'danger');
                $this->redirect('this');
            }

            if (!$this->validateSubeventsCapacities($selectedSubevents)) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_subevents_occupied', 'danger');
                $this->redirect('this');
            }

            if(!$this->validateSubeventsRegistered($selectedSubevents, $this->user, $application->getId())) {
                $this->getPresenter()->flashMessage('admin.users.users_applications_subevents_registered', 'danger');
                $this->redirect('this');
            }
        }

        //zpracovani zmen
        $this->applicationRepository->getEntityManager()->transactional(function($em) use($selectedRoles, $selectedSubevents, $application, $values) {
            $oldPaymentDate = $application->getPaymentDate();

            if ($application->isFirst()) {
                $this->user->setRoles($selectedRoles);
                $this->userRepository->save($this->user);
            }

            if ($this->subeventRepository->explicitSubeventsExists())
                $application->setSubevents($selectedSubevents);
            $application->setVariableSymbol($values['variableSymbol']);
            $application->setPaymentMethod($values['paymentMethod']);
            $application->setPaymentDate($values['paymentDate']);
            $application->setIncomeProofPrintedDate($values['incomeProofPrintedDate']);
            $application->setMaturityDate($values['maturityDate']);

            if ($application->isFirst()) {
                foreach ($this->user->getApplications() as $application) {
                    if ($application->isFirst())
                        $fee = $this->applicationService->countFee($selectedRoles, $selectedSubevents);
                    else
                        $fee = $this->applicationService->countFee($selectedRoles, $application->getSubevents(), false);
                    $application->setFee($fee);
                    $application->setState($fee == 0 || $application->getPaymentDate()
                        ? ApplicationState::PAID
                        : ApplicationState::WAITING_FOR_PAYMENT);
                    $this->applicationRepository->save($application);
                }
            } else {
                $fee = $this->applicationService->countFee($this->user->getRoles(), $selectedSubevents, false);
                $application->setFee($fee);
                $application->setState($fee == 0 || $application->getPaymentDate()
                    ? ApplicationState::PAID
                    : ApplicationState::WAITING_FOR_PAYMENT);
                $this->applicationRepository->save($application);
            }

            $this->programRepository->updateUserPrograms($this->user);
            $this->userRepository->save($this->user);

            if ($values['paymentDate'] !== NULL && $oldPaymentDate === NULL) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::PAYMENT_CONFIRMED, [
                    TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
                ]);
            }
        });

        $this->getPresenter()->flashMessage('admin.users.users_applications_saved', 'success');
        $this->redirect('this');
    }

    /**
     * Vygeneruje příjmový pokladní doklad.
     * @param $id
     */
    public function handleGeneratePaymentProofCash($id)
    {
        $this->pdfExportService->generateApplicationsPaymentProof(
            $application = $this->applicationRepository->findById($id),
            "prijmovy-pokladni-doklad.pdf"
        );
    }

    /**
     * Vygeneruje potvrzení o přijetí platby.
     * @param $id
     */
    public function handleGeneratePaymentProofBank($id)
    {
        $this->pdfExportService->generateApplicationsPaymentProof(
            $application = $this->applicationRepository->findById($id),
            "potvrzeni-o-prijeti-platby.pdf"
        );
    }

    /**
     * Odhlásí uživatele ze semináře.
     * @param $id
     */
    public function handleCancelRegistration($id)
    {
        $user = $this->applicationRepository->findById($id)->getUser();

        $this->applicationRepository->getEntityManager()->transactional(function($em) use($user) {
            $user->setRoles(new ArrayCollection([$this->roleRepository->findBySystemName(Role::NONREGISTERED)]));
            foreach ($user->getApplications() as $application) {
                $this->applicationRepository->remove($application);
            }
            $this->userRepository->save($user);

            $this->programRepository->updateUserPrograms($user);
            $this->userRepository->save($user);
        });

        $this->getPresenter()->flashMessage('admin.users.users_applications_registration_canceled', 'success');
        $this->redirect('this');
    }

    /**
     * Zruší přihlášku.
     * @param $id
     */
    public function handleCancelApplication($id)
    {
        $application = $this->applicationRepository->findById($id);
        $user = $application->getUser();

        $this->applicationRepository->getEntityManager()->transactional(function($em) use($application, $user) {
            $application->setState(ApplicationState::CANCELED);
            $this->applicationRepository->save($application);

            $this->programRepository->updateUserPrograms($user);
            $this->userRepository->save($user);
        });

        $this->getPresenter()->flashMessage('admin.users.users_applications_application_canceled', 'success');
        $this->redirect('this');
    }

    /**
     * Ověří, že je vybrána alespoň jedna podakce.
     * @param $selectedSubevents
     * @return bool
     */
    private function validateSubeventsEmpty($selectedSubevents)
    {
        if ($selectedSubevents->isEmpty())
            return FALSE;
        return TRUE;
    }

    /**
     * Ověří obsazenost podakcí.
     * @param $selectedSubevents
     * @return bool
     */
    private function validateSubeventsCapacities($selectedSubevents)
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
     * Ověří, zda uživatel podakci již nemá.
     * @param $selectedSubevents
     * @param User $user
     * @param $applicationId
     * @return bool
     */
    private function validateSubeventsRegistered($selectedSubevents, User $user, $applicationId)
    {
        foreach ($selectedSubevents as $subevent) {
            foreach ($user->getApplications() as $application) {
                if ($application->getId() != $applicationId
                    && ($application->getState() == ApplicationState::PAID
                        || $application->getState() == ApplicationState::WAITING_FOR_PAYMENT)
                    && $application->getSubevents()->contains($subevent))
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří obsazenost rolí.
     * @param $selectedRoles
     * @return bool
     */
    private function validateRolesCapacities($selectedRoles)
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
     * Ověří, že je vybrána alespoň jedna role.
     * @param $selectedRoles
     * @return bool
     */
    private function validateRolesEmpty($selectedRoles)
    {
        if ($selectedRoles->isEmpty())
            return FALSE;
        return TRUE;
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


