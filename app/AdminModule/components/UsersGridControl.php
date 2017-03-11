<?php

namespace App\AdminModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\PaymentType;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

class UsersGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var UserRepository */
    private $userRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;

    /** @var PdfExportService */
    private $pdfExportService;

    /** @var ExcelExportService */
    private $excelExportService;


    public function __construct(Translator $translator, UserRepository $userRepository,
                                SettingsRepository $settingsRepository, CustomInputRepository $customInputRepository,
                                RoleRepository $roleRepository, ProgramRepository $programRepository,
                                BlockRepository $blockRepository, PdfExportService $pdfExportService,
                                ExcelExportService $excelExportService, Session $session)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->settingsRepository = $settingsRepository;
        $this->customInputRepository = $customInputRepository;
        $this->roleRepository = $roleRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository = $blockRepository;
        $this->pdfExportService = $pdfExportService;
        $this->excelExportService = $excelExportService;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }

    public function createComponentUsersGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();


        $grid->addGroupAction('admin.users.users_group_action_approve')
            ->onSelect[] = [$this, 'groupApprove'];

        $grid->addGroupMultiSelectAction('admin.users.users_group_action_change_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED]))
            ->onSelect[] = [$this, 'groupChangeRoles'];

        $grid->addGroupAction('admin.users.users_group_action_mark_attended')
            ->onSelect[] = [$this, 'groupMarkAttended'];

        $grid->addGroupAction('admin.users.users_group_action_mark_paid_today', $this->preparePaymentMethodOptionsWithoutEmpty())
            ->onSelect[] = [$this, 'groupMarkPaidToday'];

        $grid->addGroupAction('admin.users.users_group_action_generate_payment_proofs')
            ->onSelect[] = [$this, 'groupGeneratePaymentProofs'];

        $grid->addGroupAction('admin.users.users_group_action_export_roles')
            ->onSelect[] = [$this, 'groupExportRoles'];

        $grid->addGroupAction('admin.users.users_group_action_export_schedules')
            ->onSelect[] = [$this, 'groupExportSchedules'];


        $grid->addColumnText('displayName', 'admin.users.users_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('username', 'admin.users.users_username')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('roles', 'admin.users.users_roles', 'roles')
            ->setRenderer(function ($row) {
                $roles = [];
                foreach ($row->getRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            })
            ->setFilterMultiSelect($this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED]))
            ->setCondition(function($qb, $values) {
                $qb->join('u.roles', 'r')->where('r.id IN (:ids)')->setParameter(':ids', $values);
            });

        $columnApproved = $grid->addColumnStatus('approved', 'admin.users.users_approved');
        $columnApproved
            ->addOption(false, 'admin.users.users_approved_unapproved')
                ->setClass('btn-danger')
                ->endOption()
            ->addOption(true, 'admin.users.users_approved_approved')
                ->setClass('btn-success')
                ->endOption()
            ->onChange[] = [$this, 'changeApproved'];
        $columnApproved
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_approved_unapproved',
                '1' => 'admin.users.users_approved_approved'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unit', 'admin.users.users_membership')
            ->setRendererOnCondition(function ($row) {
                    return Html::el('span')
                        ->style('color: red')
                        ->setText($row->isMember() ?
                            $this->translator->translate('admin.users.users_membership_no') :
                            $this->translator->translate('admin.users.users_membership_not_connected'));
                }, function ($row) {
                    return $row->getUnit() === null;
                }
            )
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(function($qb, $sort) {
                $sort = $sort['age'] == 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sort);
            });

        $grid->addColumnText('city', 'admin.users.users_city')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'admin.users.users_fee');

        $grid->addColumnText('variableSymbol', 'admin.users.users_variable_symbol')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method')
            ->setRenderer(function ($row) {
                if ($row->getPaymentMethod())
                    return $this->translator->translate('common.payment.' . $row->getPaymentMethod());
                return null;
            })
            ->setSortable()
            ->setFilterSelect($this->preparePaymentMethodFilterOptions())
            ->setTranslateOptions();

        $grid->addColumnDateTime('paymentDate', 'admin.users.users_payment_date')
            ->setSortable();

        $grid->addColumnDateTime('incomeProofPrintedDate', 'admin.users.users_income_proof_printed_date')
            ->setSortable();

        $grid->addColumnDateTime('firstLogin', 'admin.users.users_first_login')
            ->setSortable();

        $columnAttended = $grid->addColumnStatus('attended', 'admin.users.users_attended');
        $columnAttended
            ->addOption(false, 'admin.users.users_attended_no')
                ->setClass('btn-danger')
                ->endOption()
            ->addOption(true, 'admin.users.users_attended_yes')
                ->setClass('btn-success')
                ->endOption()
            ->onChange[] = [$this, 'changeAttended'];
        $columnAttended
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.users.users_attended_no',
                '1' => 'admin.users.users_attended_yes'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unregisteredMandatoryBlocks', 'admin.users.users_not_registered_mandatory_blocks')
            ->setRenderer(function ($row){
                if (!$row->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS) || !$row->isApproved())
                    return null;

                $unregisteredMandatoryBlocksNames = $this->blockRepository->findUserMandatoryNotRegisteredNames($row);
                $unregisteredMandatoryBlocksNamesText = implode(', ', $unregisteredMandatoryBlocksNames);
                return Html::el('span')
                    ->setAttribute('data-toggle', 'tooltip')
                    ->setAttribute('title', $unregisteredMandatoryBlocksNamesText)
                    ->setText(count($unregisteredMandatoryBlocksNames));
            });

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $grid->addColumnText('customInput' . $customInput->getId(), $customInput->getName())
                ->setRenderer(function ($row) use ($customInput) {
                    $customInputValue = $row->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        if ($customInputValue->getInput()->getType() == CustomInput::TEXT)
                            return $customInputValue->getValue();
                        else {
                            return $customInputValue->getValue() ?
                                $this->translator->translate('admin.common.yes') :
                                $this->translator->translate('admin.common.no');
                        }
                    }
                    return null;
                });
        }


        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addSelect('paymentMethod', '', $this->preparePaymentMethodOptionsWithEmpty());
            $container->addDatePicker('paymentDate', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'paymentMethod' => $item->getPaymentMethod(),
                'paymentDate' => $item->getPaymentDate()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('detail', 'admin.common.detail', 'Users:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->setColumnsSummary(['fee']);
    }

    public function edit($id, $values) {
        $user = $this->userRepository->findById($id);
        $user->setPaymentMethod($values['paymentMethod']);
        $user->setPaymentDate($values['paymentDate']);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
        }
        else {
            $this->redirect('this');
        }
    }

    public function changeApproved($id, $approved) {
        $user = $this->userRepository->findById($id);

        $over = false;
        if ($approved && !$user->isApproved()) {
            foreach ($user->getRoles() as $role) {
                $count = $this->roleRepository->countUnoccupiedInRole($role);
                if ($count !== null && $count < 1) {
                    $over = true;
                    break;
                }
            }
        }

        $p = $this->getPresenter();

        if ($over) {
            $p->flashMessage('admin.users.users_change_approved_error', 'danger');
        }
        else {
            $user->setApproved($approved);
            $this->userRepository->save($user);

            $p->flashMessage('admin.users.users_changed_approved', 'success');
        }

        $this->redirect('this');
    }

    public function changeAttended($id, $attended) {
        $user = $this->userRepository->findById($id);
        $user->setAttended($attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    public function groupApprove(array $ids) {
        $users = $this->userRepository->findUsersByIds($ids);
        $rolesWithLimitedCapacity = $this->roleRepository->findAllWithLimitedCapacity();
        $unoccupiedCounts = $this->roleRepository->countUnoccupiedInRoles($rolesWithLimitedCapacity);

        foreach ($users as $user) {
            if (!$user->isApproved()) {
                foreach ($user->getRoles() as $role) {
                    if ($role->hasLimitedCapacity())
                        $unoccupiedCounts[$role->getId()]--;
                }
            }
        }

        $over = false;
        foreach ($unoccupiedCounts as $count) {
            if ($count < 0) {
                $over = true;
                break;
            }
        }

        $p = $this->getPresenter();

        if ($over) {
            $p->flashMessage('admin.users.users_group_action_approve_error', 'danger');
        }
        else {
            foreach ($users as $user) {
                $user->setApproved(true);
                $this->userRepository->save($user);
            }

            $p->flashMessage('admin.users.users_group_action_approved', 'success');
        }

        $this->redirect('this');
    }

    public function groupChangeRoles(array $ids, $value) {
        $users = $this->userRepository->findUsersByIds($ids);
        $selectedRoles = $this->roleRepository->findRolesByIds($value);

        $p = $this->getPresenter();

        $error = false;

        //neni vybrana zadna role
        if ($selectedRoles->isEmpty()) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_empty', 'danger');
            $error = true;
        }

        //pokud je vybrana role neregistrovany, nesmi byt zadna vybrana jina role
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        if ($selectedRoles->contains($nonregisteredRole) && $selectedRoles->count() > 1) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_nonregistered', 'danger');
            $error = true;
        }

        //v rolich musi byt dostatek volnych mist
        $unoccupiedCounts = $this->roleRepository->countUnoccupiedInRoles($selectedRoles);
        foreach ($selectedRoles as $role) {
            if ($role->hasLimitedCapacity()) {
                foreach ($users as $user) {
                    if ($user->isApproved() && !$user->isInRole($role))
                        $unoccupiedCounts[$role->getId()]--;
                }
            }
        }
        foreach ($unoccupiedCounts as $count) {
            if ($count < 0) {
                $p->flashMessage('admin.users.users_group_action_change_roles_error_capacity', 'danger');
                $error = true;
                break;
            }
        }

        if (!$error) {
            foreach ($users as $user) {
                $user->setRoles($selectedRoles);
                $this->userRepository->save($user);
            }

            $this->programRepository->updateUsersPrograms($users->toArray());
            $this->userRepository->getEntityManager()->flush();

            $p->flashMessage('admin.users.users_group_action_changed_roles', 'success');
        }

        $this->redirect('this');
    }

    public function groupMarkAttended(array $ids) {
        $this->userRepository->setAttended($ids);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_marked_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function groupMarkPaidToday(array $ids, $value) {
        foreach ($ids as $id) {
            $user = $this->userRepository->findById($id);
            if ($user->isPaying()) {
                $user->setPaymentMethod($value);
                $user->setPaymentDate(new \DateTime());
                $this->userRepository->save($user);
            }
        }

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_marked_paid_today', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function groupGeneratePaymentProofs(array $ids) {
        $this->sessionSection->userIds = $ids;
        $this->redirect('generatepaymentproofs'); //presmerovani kvuli zruseni ajax
    }

    public function groupExportRoles(array $ids) {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportroles'); //presmerovani kvuli zruseni ajax
    }

    public function groupExportSchedules(array $ids) {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportschedules'); //presmerovani kvuli zruseni ajax
    }

    public function handleGeneratePaymentProofs() {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $usersToGenerate = [];

        foreach ($users as $user) {
            if ($user->getPaymentDate()) {
                if (!$user->getIncomeProofPrintedDate()) {
                    $user->setIncomeProofPrintedDate(new \DateTime());
                    $this->userRepository->save($user);
                }
                $usersToGenerate[] = $user;
            }
        }

        $this->pdfExportService->generatePaymentProofs($usersToGenerate, "doklady.pdf");
    }

    public function handleExportRoles() {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $roles = $this->roleRepository->findAll();

        $response = $this->excelExportService->exportUsersRoles($users, $roles, "role-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    public function handleExportSchedules() {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSchedules($users, "harmonogramy-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    private function preparePaymentMethodOptionsWithoutEmpty() {
        $options = [];
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }

    private function preparePaymentMethodOptionsWithEmpty() {
        $options = [];
        $options[''] = '';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }

    private function preparePaymentMethodFilterOptions() {
        $options = [];
        $options[''] = 'admin.common.all';
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }
}