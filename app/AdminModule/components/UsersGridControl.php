<?php

namespace App\AdminModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\MailService;
use App\Services\PdfExportService;
use App\Services\ProgramService;
use App\Services\UserService;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    /** @var MailService */
    private $mailService;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var ApplicationService */
    private $applicationService;

    /** @var UserService */
    private $userService;

    /** @var ProgramService */
    private $programService;


    /**
     * UsersGridControl constructor.
     * @param Translator $translator
     * @param UserRepository $userRepository
     * @param SettingsRepository $settingsRepository
     * @param CustomInputRepository $customInputRepository
     * @param RoleRepository $roleRepository
     * @param ProgramRepository $programRepository
     * @param BlockRepository $blockRepository
     * @param PdfExportService $pdfExportService
     * @param ExcelExportService $excelExportService
     * @param MailService $mailService
     * @param Session $session
     * @param SubeventRepository $subeventRepository
     * @param ApplicationRepository $applicationRepository
     * @param ApplicationService $applicationService
     * @param UserService $userService
     * @param ProgramService $programService
     */
    public function __construct(Translator $translator, UserRepository $userRepository,
                                SettingsRepository $settingsRepository, CustomInputRepository $customInputRepository,
                                RoleRepository $roleRepository, ProgramRepository $programRepository,
                                BlockRepository $blockRepository, PdfExportService $pdfExportService,
                                ExcelExportService $excelExportService, MailService $mailService, Session $session,
                                SubeventRepository $subeventRepository, ApplicationRepository $applicationRepository,
                                ApplicationService $applicationService, UserService $userService,
                                ProgramService $programService)
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
        $this->mailService = $mailService;
        $this->subeventRepository = $subeventRepository;
        $this->applicationRepository = $applicationRepository;
        $this->applicationService = $applicationService;
        $this->userService = $userService;
        $this->programService = $programService;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/users_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Ublaboo\DataGrid\Exception\DataGridColumnStatusException
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentUsersGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);

        $grid->addGroupAction('admin.users.users_group_action_approve')
            ->onSelect[] = [$this, 'groupApprove'];

        $grid->addGroupMultiSelectAction('admin.users.users_group_action_change_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithCapacity([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->onSelect[] = [$this, 'groupChangeRoles'];

        $grid->addGroupAction('admin.users.users_group_action_mark_attended')
            ->onSelect[] = [$this, 'groupMarkAttended'];

        $grid->addGroupAction('admin.users.users_group_action_mark_paid_today', $this->preparePaymentMethodOptionsWithoutEmpty())
            ->onSelect[] = [$this, 'groupMarkPaidToday'];

        $grid->addGroupAction('admin.users.users_group_action_generate_payment_proofs')
            ->onSelect[] = [$this, 'groupGeneratePaymentProofs'];

        $grid->addGroupAction('admin.users.users_group_action_export_users')
            ->onSelect[] = [$this, 'groupExportUsers'];

        $grid->addGroupAction('admin.users.users_group_action_export_subevents_and_categories')
            ->onSelect[] = [$this, 'groupExportSubeventsAndCategories'];

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

        $grid->addColumnText('roles', 'admin.users.users_roles', 'rolesText')
            ->setFilterMultiSelect($this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED]))
            ->setCondition(function ($qb, $values) {
                $qb->join('u.roles', 'r')
                    ->andWhere('r.id IN (:rids)')
                    ->setParameter('rids', $values);
            });

        $grid->addColumnText('subevents', 'admin.users.users_subevents', 'subeventsText')
            ->setFilterMultiSelect($this->subeventRepository->getSubeventsOptions())
            ->setCondition(function ($qb, $values) {
                $qb->join('u.applications', 'aSubevents')
                    ->join('aSubevents.subevents', 's')
                    ->andWhere('s.id IN (:sids)')
                    ->setParameter('sids', $values);
            });

        $columnApproved = $grid->addColumnStatus('approved', 'admin.users.users_approved');
        $columnApproved
            ->addOption(FALSE, 'admin.users.users_approved_unapproved')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.users.users_approved_approved')
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
                    ->setText($this->userService->getMembershipText($row));
            }, function ($row) {
                return $row->getUnit() === NULL;
            })
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(function (QueryBuilder $qb, $sort) {
                $sort = $sort['age'] == 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sort);
            });

        $grid->addColumnText('city', 'admin.users.users_city')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnNumber('fee', 'admin.users.users_fee');

        $grid->addColumnNumber('feeRemaining', 'admin.users.users_fee_remaining');

        $grid->addColumnText('variableSymbol', 'admin.users.users_variable_symbol', 'variableSymbolsText')
            ->setFilterText()
            ->setCondition(function (QueryBuilder $qb, $value) {
                $qb->join('u.applications', 'aVariableSymbol')
                    ->join('aVariableSymbol.variableSymbol', 'avsVariableSymbol')
                    ->andWhere('avsVariableSymbol.variableSymbol LIKE :variableSymbol')
                    ->setParameter(':variableSymbol', $value . '%');
            });

        $grid->addColumnText('paymentMethod', 'admin.users.users_payment_method')
            ->setRenderer(function ($row) {
                return $this->userService->getPaymentMethodText($row);
            });

        $grid->addColumnDateTime('lastPaymentDate', 'admin.users.users_last_payment_date');

        $grid->addColumnDateTime('rolesApplicationDate', 'admin.users.users_roles_application_date')
            ->setFormat('j. n. Y H:i');

        $columnAttended = $grid->addColumnStatus('attended', 'admin.users.users_attended');
        $columnAttended
            ->addOption(FALSE, 'admin.users.users_attended_no')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.users.users_attended_yes')
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
            ->setRenderer(function (User $row) {
                if (!$row->isAllowedRegisterPrograms())
                    return NULL;

                $unregisteredUserMandatoryBlocks = $this->programService->getUnregisteredUserMandatoryBlocksNames($row);
                return Html::el('span')
                    ->setAttribute('data-toggle', 'tooltip')
                    ->setAttribute('title', implode(', ', $unregisteredUserMandatoryBlocks->toArray()))
                    ->setText($unregisteredUserMandatoryBlocks->count());
            });

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $grid->addColumnText('customInput' . $customInput->getId(), $this->truncate($customInput->getName(), 20))
                ->setRenderer(function (User $row) use ($customInput) {
                    $customInputValue = $row->getCustomInputValue($customInput);
                    if ($customInputValue) {
                        switch ($customInputValue->getInput()->getType()) {
                            case CustomInput::TEXT:
                                return $this->truncate($customInputValue->getValue(), 20);

                            case CustomInput::CHECKBOX:
                                return $customInputValue->getValue()
                                    ? $this->translator->translate('admin.common.yes')
                                    : $this->translator->translate('admin.common.no');

                            case CustomInput::SELECT:
                                return $customInputValue->getValueOption();

                            case CustomInput::FILE:
                                return $customInputValue->getValue()
                                    ? Html::el('a')
                                        ->setAttribute('href', $this->getPresenter()->getTemplate()->basePath
                                            . '/files' . $customInputValue->getValue())
                                        ->setAttribute('target', '_blank')
                                        ->setAttribute('class', 'btn btn-xs btn-default')
                                        ->addHtml(
                                            Html::el('span')->setAttribute('class', 'fa fa-download')
                                        )
                                    : '';
                        }
                    }
                    return NULL;
                });
        }

        $grid->addColumnText('note', 'admin.users.users_private_note')
            ->setFilterText();

        $grid->addToolbarButton('Users:add')
            ->setIcon('plus')
            ->setText('admin.users.users_add_lector');

        $grid->addAction('detail', 'admin.common.detail', 'Users:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function (User $item) {
            return $item->isExternal();
        });

        $grid->setColumnsSummary(['fee']);
    }

    /**
     * Zpracuje odstranění externího uživatele.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $user = $this->userRepository->findById($id);

        $this->userRepository->remove($user);

        $this->getPresenter()->flashMessage('admin.users.users_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní stav uživatele.
     * @param $id
     * @param $approved
     * @throws \Nette\Application\AbortException
     */
    public function changeApproved($id, $approved)
    {
        $user = $this->userRepository->findById($id);
        $user->setApproved($approved);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_approved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní účast uživatele na semináři.
     * @param $id
     * @param $attended
     * @throws \Nette\Application\AbortException
     */
    public function changeAttended($id, $attended)
    {
        $user = $this->userRepository->findById($id);
        $user->setAttended($attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['usersGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Hromadně schválí uživatele.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function groupApprove(array $ids)
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->userRepository->getEntityManager()->transactional(function ($em) use ($users) {
            foreach ($users as $user) {
                $user->setApproved(TRUE);
                $this->userRepository->save($user);
            }
        });

        $this->getPresenter()->flashMessage('admin.users.users_group_action_approved', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně nastaví role.
     * @param array $ids
     * @param $value
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function groupChangeRoles(array $ids, $value)
    {
        $users = $this->userRepository->findUsersByIds($ids);
        $selectedRoles = $this->roleRepository->findRolesByIds($value);

        $p = $this->getPresenter();

        //neni vybrana zadna role
        if ($selectedRoles->isEmpty()) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_empty', 'danger');
            $this->redirect('this');
        }

        //v rolich musi byt dostatek volnych mist
        $capacitiesOk = $selectedRoles->forAll(function (int $key, Role $role) use ($users) {
            if (!$role->hasLimitedCapacity())
                return TRUE;

            $capacityNeeded = $users->count();

            if ($capacityNeeded <= $role->getCapacity())
                return TRUE;

            foreach ($users as $user) {
                if ($user->isInRole($role))
                    $capacityNeeded--;
            }

            if ($capacityNeeded <= $role->getCapacity())
                return TRUE;

            return FALSE;
        });

        if (!$capacitiesOk) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_capacity', 'danger');
            $this->redirect('this');
        }

        $loggedUser = $this->userRepository->findById($p->getUser()->id);

        $this->userRepository->getEntityManager()->transactional(function ($em) use ($selectedRoles, $users, $loggedUser) {
            foreach ($users as $user) {
                $this->applicationService->updateRoles($user, $selectedRoles, $loggedUser, TRUE);
            }
        });

        $p->flashMessage('admin.users.users_group_action_changed_roles', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně označí uživatele jako zúčastněné.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function groupMarkAttended(array $ids)
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->userRepository->getEntityManager()->transactional(function ($em) use ($users) {
            foreach ($users as $user) {
                $user->setAttended(TRUE);
                $this->userRepository->save($user);
            }
        });

        $this->getPresenter()->flashMessage('admin.users.users_group_action_marked_attended', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně označí uživatele jako zaplacené dnes.
     * @param array $ids
     * @param $value
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function groupMarkPaidToday(array $ids, $value)
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $p = $this->getPresenter();

        $loggedUser = $this->userRepository->findById($p->getUser()->id);

        $this->userRepository->getEntityManager()->transactional(function ($em) use ($users, $value, $loggedUser) {
            foreach ($users as $user) {
                foreach ($user->getWaitingForPaymentApplications() as $application) {
                    $this->applicationService->updatePayment($application, $application->getVariableSymbolText(),
                        $value, new \DateTime(), $application->getIncomeProofPrintedDate(),
                        $application->getMaturityDate(), $loggedUser);
                }
            }
        });

        $p->flashMessage('admin.users.users_group_action_marked_paid_today', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně vygeneruje potvrzení o zaplacení.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupGeneratePaymentProofs(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('generatepaymentproofs'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Hromadně vyexportuje seznam uživatelů.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupExportUsers(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportusers'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů.
     * @throws \PHPExcel_Exception
     * @throws \Nette\Application\AbortException
     */
    public function handleExportUsers()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersList($users, "seznam-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s rolemi.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupExportRoles(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportroles'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů s rolemi.
     * @throws \PHPExcel_Exception
     * @throws \Nette\Application\AbortException
     */
    public function handleExportRoles()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $roles = $this->roleRepository->findAll();

        $response = $this->excelExportService->exportUsersRoles($users, $roles, "role-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s podakcemi a programy podle kategorií.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupExportSubeventsAndCategories(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportsubeventsandcategories'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export seznamu uživatelů s podakcemi a programy podle kategorií.
     * @throws \PHPExcel_Exception
     * @throws \Nette\Application\AbortException
     */
    public function handleExportSubeventsAndCategories()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSubeventsAndCategories($users, "podakce-a-kategorie.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje harmonogramy uživatelů.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupExportSchedules(array $ids)
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportschedules'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export harmonogramů uživatelů.
     * @throws \PHPExcel_Exception
     * @throws \Nette\Application\AbortException
     */
    public function handleExportSchedules()
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSchedules($users, "harmonogramy-uzivatelu.xlsx");

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Vygeneruje doklady o zaplacení.
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function handleGeneratePaymentProofs()
    {
        $ids = $this->session->getSection('srs')->userIds;
        $users = $this->userRepository->findUsersByIds($ids);
        $this->pdfExportService->generateUsersPaymentProofs($users, "doklady.pdf",
            $this->userRepository->findById($this->getPresenter()->getUser()->id)
        );
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Bez prázdné možnosti.
     * @return array
     */
    private function preparePaymentMethodOptionsWithoutEmpty()
    {
        $options = [];
        foreach (PaymentType::$types as $type)
            $options[$type] = 'common.payment.' . $type;
        return $options;
    }

    /**
     * Zkrátí $text na $length znaků a doplní '...'.
     * @param $text
     * @param $length
     * @return bool|string
     */
    private function truncate($text, $length)
    {
        if (strlen($text) > $length) {
            $text = $text . " ";
            $text = mb_substr($text, 0, $length, 'UTF-8');
            $text = mb_substr($text, 0, strrpos($text, ' '), 'UTF-8');
            $text = $text . "...";
        }
        return $text;
    }
}
