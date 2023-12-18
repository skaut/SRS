<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\CustomInput\CustomCheckbox;
use App\Model\CustomInput\CustomCheckboxValue;
use App\Model\CustomInput\CustomDate;
use App\Model\CustomInput\CustomDateTime;
use App\Model\CustomInput\CustomFileValue;
use App\Model\CustomInput\CustomMultiSelect;
use App\Model\CustomInput\CustomSelect;
use App\Model\CustomInput\CustomText;
use App\Model\CustomInput\CustomTextValue;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Queries\SettingIntValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Model\Group\Repositories\GroupRepository;
use App\Model\Group\Group;
use App\Services\AclService;
use App\Services\ApplicationService;
use App\Services\ExcelExportService;
use App\Services\QueryBus;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
use App\Services\SubeventService;
use App\Services\UserService;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;
use function basename;

/**
 * Komponenta pro správu rolí.
 */
class GroupUsersGridControl extends Control
{
    private SessionSection $sessionSection;
    private User|null $user = null;

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly Translator $translator,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly GroupRepository $groupRepository,
        private readonly CustomInputRepository $customInputRepository,
        private readonly RoleRepository $roleRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly Session $session,
        private readonly AclService $aclService,
        private readonly ApplicationService $applicationService,
        private readonly UserService $userService,
        private readonly SkautIsEventEducationService $skautIsEventEducationService,
        private readonly SkautIsEventGeneralService $skautIsEventGeneralService,
        private readonly SubeventService $subeventService,
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/groupUsers_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentGroupUsersGrid(string $name): DataGrid
    {
        $this->user = $this->getPresenter()->getDbUser();
        $groupId = $this->user->getGroupId();
        
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->userRepository->createQueryBuilder('u')
            ->where('u.groupId = :groupId')
            ->setParameter('groupId', $groupId));
        $grid->setDefaultSort(['lastName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

 
        $grid->addColumnText('displayName', 'admin.users.users_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('username', 'admin.users.users_username')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('roles', 'admin.users.users_roles', 'rolesText')
            ->setFilterMultiSelect($this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED]))
            ->setCondition(static function (QueryBuilder $qb, ArrayHash $values): void {
                $qb->join('u.roles', 'uR')
                    ->andWhere('uR.id IN (:rids)')
                    ->setParameter('rids', (array) $values);
            });


        $columnApproved  = $grid->addColumnStatus('approved', 'admin.users.users_approved');
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
                '1' => 'admin.users.users_approved_approved',
            ])
            ->setTranslateOptions();

        $grid->addColumnText('unit', 'admin.users.users_membership')
            ->setRendererOnCondition(
                fn (User $row) => Html::el('span')
                    ->class('text-danger')
                    ->setText($this->userService->getMembershipText($row)),
                static fn (User $row) => $row->getUnit() === null
            )
            ->setSortable()
            ->setSortableCallback(static function (QueryBuilder $qb, array $sort): void {
                $sortOrig = $sort['unit'];
                $sortRev  = $sort['unit'] === 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.unit', $sortOrig)
                    ->addOrderBy('u.externalLector', $sortRev)
                    ->addOrderBy('u.member', $sortRev);
            })
            ->setFilterText();

        $grid->addColumnNumber('age', 'admin.users.users_age')
            ->setSortable()
            ->setSortableCallback(static function (QueryBuilder $qb, array $sort): void {
                $sortRev = $sort['age'] === 'DESC' ? 'ASC' : 'DESC';
                $qb->orderBy('u.birthdate', $sortRev);
            });

        $grid->addColumnText('email', 'admin.users.users_email')
            ->setRenderer(static fn (User $row) => Html::el('a')
                ->href('mailto:' . $row->getEmail())
                ->setText($row->getEmail()))
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('phone', 'admin.users.users_phone')
            ->setRenderer(static fn (User $row) => Html::el('a')
                ->href('tel:' . $row->getPhone())
                ->setText($row->getPhone()))
            ->setFilterText();

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.users.users_delete_confirm'),
            ]);
//        $grid->allowRowsAction('delete', static fn (User $item) => $item->isExternalLector());


        return $grid;
    }

    /**
     * Zpracuje odstranění externího uživatele.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $p = $this->getPresenter();
        
        $removeUser = $this->userRepository->findById($id);
        $userGroupId = $removeUser->setGroupId(0);
        $this->userRepository->save($removeUser);
        
        if($user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_MEMBER)->getName())){
            $userRoleUnregistered = $this->roleRepository->findById(2);
            $removeUser->addRole($userRoleUnregistered);
            $userRoleMember = $this->roleRepository->findById(10);
            $removeUser->removeRole($userRoleMember);
        }
        
        $p->flashMessage('admin.users.users_deleted', 'success');
        $p->redirect('this');
    }

    /**
     * Změní stav uživatele.
     *
     * @throws AbortException
     */
    public function changeApproved(string $id, string $approved): void
    {
        $user = $this->userRepository->findById((int) $id);

        $this->userService->setApproved($user, (bool) $approved);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_approved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('usersGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Změní účast uživatele na semináři.
     *
     * @throws AbortException
     */
    public function changeAttended(string $id, string $attended): void
    {
        $user = $this->userRepository->findById((int) $id);
        $user->setAttended((bool) $attended);
        $this->userRepository->save($user);

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_changed_attended', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('usersGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Hromadně schválí uživatele.
     *
     * @param int[] $ids
     *
     * @throws Throwable
     */
    public function groupApprove(array $ids): void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->em->wrapInTransaction(function () use ($users): void {
            foreach ($users as $user) {
                $this->userService->setApproved($user, true);
            }
        });

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_approved', 'success');

        $this->reload();
    }

    /**
     * Hromadně nastaví role.
     *
     * @param int[] $ids
     * @param int[] $value
     *
     * @throws Throwable
     */
    public function groupChangeRoles(array $ids, array $value): void
    {
        $users         = $this->userRepository->findUsersByIds($ids);
        $selectedRoles = $this->roleRepository->findRolesByIds($value);

        $p = $this->getPresenter();
        assert($p instanceof AdminBasePresenter);

        // neni vybrana zadna role
        if ($selectedRoles->isEmpty()) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_empty', 'danger');
            $this->reload();

            return;
        }

        // v rolich musi byt dostatek volnych mist
        $capacitiesOk = $selectedRoles->forAll(static function (int $key, Role $role) use ($users) {
            if (! $role->hasLimitedCapacity()) {
                return true;
            }

            $capacityNeeded = $users->count();

            if ($capacityNeeded <= $role->getCapacity()) {
                return true;
            }

            foreach ($users as $user) {
                if ($user->isInRole($role)) {
                    $capacityNeeded--;
                }
            }

            return $capacityNeeded <= $role->getCapacity();
        });

        if (! $capacitiesOk) {
            $p->flashMessage('admin.users.users_group_action_change_roles_error_capacity', 'danger');
            $this->reload();

            return;
        }

        $loggedUser = $p->getDbUser();

        $this->em->wrapInTransaction(function () use ($selectedRoles, $users, $loggedUser): void {
            foreach ($users as $user) {
                $this->applicationService->updateRoles($user, $selectedRoles, $loggedUser, true);
            }
        });

        $p->flashMessage('admin.users.users_group_action_changed_roles', 'success');
        $this->reload();
    }

    /**
     * Hromadně označí uživatele jako zúčastněné.
     *
     * @param int[] $ids
     *
     * @throws Throwable
     */
    public function groupMarkAttended(array $ids): void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $this->em->wrapInTransaction(function () use ($users): void {
            foreach ($users as $user) {
                $user->setAttended(true);
                $this->userRepository->save($user);
            }
        });

        $p = $this->getPresenter();
        $p->flashMessage('admin.users.users_group_action_marked_attended', 'success');
        $this->reload();
    }

    /**
     * Hromadně označí uživatele jako zaplacené dnes.
     *
     * @param int[] $ids
     *
     * @throws Throwable
     */
    public function groupMarkPaidToday(array $ids, string $paymentMethod): void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $p = $this->getPresenter();
        assert($p instanceof AdminBasePresenter);

        $loggedUser = $p->getDbUser();

        $this->em->wrapInTransaction(function () use ($users, $paymentMethod, $loggedUser): void {
            foreach ($users as $user) {
                foreach ($user->getWaitingForPaymentApplications() as $application) {
                    $this->applicationService->updateApplicationPayment(
                        $application,
                        $paymentMethod,
                        new DateTimeImmutable(),
                        $application->getMaturityDate(),
                        $loggedUser,
                    );
                }
            }
        });

        $p->flashMessage('admin.users.users_group_action_marked_paid_today', 'success');
        $this->reload();
    }

    /**
     * Hromadně vloží uživatele jako účastníky do skautIS.
     *
     * @param int[] $ids
     *
     * @throws Throwable
     */
    public function groupInsertIntoSkautIs(array $ids, int|null $accept): void
    {
        $users = $this->userRepository->findUsersByIds($ids);

        $p = $this->getPresenter();

        $eventId = $this->queryBus->handle(new SettingIntValueQuery(Settings::SKAUTIS_EVENT_ID));

        if ($eventId === null) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_not_connected', 'danger');
            $this->reload();

            return;
        }

        switch ($this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_TYPE))) {
            case SkautIsEventType::GENERAL:
                $skautIsEventService = $this->skautIsEventGeneralService;
                break;

            case SkautIsEventType::EDUCATION:
                $skautIsEventService = $this->skautIsEventEducationService;
                if (! $skautIsEventService->isSubeventConnected()) {
                    $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_subevent_not_connected', 'danger');
                    $this->reload();

                    return;
                }

                break;

            default:
                throw new InvalidArgumentException();
        }

        if (! $skautIsEventService->isEventDraft($eventId)) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_not_draft', 'danger');
            $this->reload();

            return;
        }

        if ($skautIsEventService->insertParticipants($eventId, $users, $accept === 1)) {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_successful', 'success');
        } else {
            $p->flashMessage('admin.users.users_group_action_insert_into_skaut_is_error_skaut_is', 'danger');
        }

        $this->reload();
    }

    /**
     * Hromadně vygeneruje potvrzení o zaplacení.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupGeneratePaymentProofs(array $ids): void
    {
        $this->sessionSection->userIds = $ids;
        $this->getPresenter()->redirect(':Export:IncomeProof:users');
    }

    /**
     * Hromadně vyexportuje seznam uživatelů.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportUsers(array $ids): void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportusers');
    }

    /**
     * Zpracuje export seznamu uživatelů.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportUsers(): void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersList($users, 'seznam-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s rolemi.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportRoles(array $ids): void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportroles');
    }

    /**
     * Zpracuje export seznamu uživatelů s rolemi.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportRoles(): void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);
        $roles = $this->roleRepository->findAll();

        $response = $this->excelExportService->exportUsersRoles($users, $roles, 'role-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje seznam uživatelů s podakcemi a programy podle kategorií.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportSubeventsAndCategories(array $ids): void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportsubeventsandcategories');
    }

    /**
     * Zpracuje export seznamu uživatelů s podakcemi a programy podle kategorií.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportSubeventsAndCategories(): void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSubeventsAndCategories($users, 'podakce-a-kategorie.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Hromadně vyexportuje harmonogramy uživatelů.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportSchedules(array $ids): void
    {
        $this->sessionSection->userIds = $ids;
        $this->redirect('exportschedules');
    }

    /**
     * Zpracuje export harmonogramů uživatelů.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportSchedules(): void
    {
        $ids = $this->session->getSection('srs')->userIds;

        $users = $this->userRepository->findUsersByIds($ids);

        $response = $this->excelExportService->exportUsersSchedules($users, 'harmonogramy-uzivatelu.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Bez prázdné možnosti.
     *
     * @return string[]
     */
    private function preparePaymentMethodOptionsWithoutEmpty(): array
    {
        $options = [];
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }

        return $options;
    }

    /**
     * Vrátí platební metody jako možnosti pro select. Včetně smíšené.
     *
     * @return string[]
     */
    private function preparePaymentMethodOptionsWithMixed(): array
    {
        $options = [];
        foreach (PaymentType::$types as $type) {
            $options[$type] = 'common.payment.' . $type;
        }

        $options[PaymentType::MIXED] = 'common.payment.' . PaymentType::MIXED;

        return $options;
    }

    /**
     * Vrátí možnosti vložení účastníků do vzdělávací akce skautIS.
     *
     * @return string[]
     */
    private function prepareInsertIntoSkautIsOptions(): array
    {
        $options    = [];
        $options[0] = 'common.skautis_event_insert_type.registered';
        $options[1] = 'common.skautis_event_insert_type.accepted';

        return $options;
    }

    /** @throws AbortException */
    private function reload(): void
    {
        $p = $this->getPresenter();
        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('usersGrid')->reload();
        } else {
            $p->redirect('this');
        }
    }
}
