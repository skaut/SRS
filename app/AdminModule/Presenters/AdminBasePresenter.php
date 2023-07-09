<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\Model\Acl\Permission;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\SrsResource;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

use function array_filter;
use function array_keys;

/**
 * BasePresenter pro AdminModule.
 */
abstract class AdminBasePresenter extends BasePresenter
{
    protected string $resource = SrsResource::ADMIN;

    #[Inject]
    public QueryBus $queryBus;

    #[Inject]
    public Authorizator $authorizator;

    #[Inject]
    public RoleRepository $roleRepository;

    #[Inject]
    public UserRepository $userRepository;

    #[Inject]
    public SkautIsService $skautIsService;

    /**
     * Přihlášený uživatel.
     */
    public User|null $dbuser = null;

    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        if ($this->user->isLoggedIn() && ! $this->skautIsService->isLoggedIn()) {
            $this->user->logout(true);
        }

        $this->user->setAuthorizator($this->authorizator);

        if (! $this->user->isLoggedIn()) {
            $this->redirect(':Auth:login', ['backlink' => $this->getHttpRequest()->getUrl()->getPath()]);
        }

        if (! $this->user->isAllowed(SrsResource::ADMIN, Permission::ACCESS)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $this->dbuser = $this->userRepository->findById($this->user->id);
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->resourceAcl           = SrsResource::ACL;
        $this->template->resourceCms           = SrsResource::CMS;
        $this->template->resourceConfiguration = SrsResource::CONFIGURATION;
        $this->template->resourceUsers         = SrsResource::USERS;
        $this->template->resourcePayments      = SrsResource::PAYMENTS;
        $this->template->resourceMailing       = SrsResource::MAILING;
        $this->template->resourceProgram       = SrsResource::PROGRAM;

        $this->template->permissionAccess            = Permission::ACCESS;
        $this->template->permissionManage            = Permission::MANAGE;
        $this->template->permissionManageOwnPrograms = Permission::MANAGE_OWN_PROGRAMS;
        $this->template->permissionManageAllPrograms = Permission::MANAGE_ALL_PROGRAMS;
        $this->template->permissionManageSchedule    = Permission::MANAGE_SCHEDULE;
        $this->template->permissionManageRooms       = Permission::MANAGE_ROOMS;
        $this->template->permissionManageCategories  = Permission::MANAGE_CATEGORIES;

        $this->template->footer      = $this->queryBus->handle(new SettingStringValueQuery(Settings::FOOTER));
        $this->template->seminarName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));

        $skautIsUserId                = $this->dbuser->getSkautISUserId();
        $skautIsRoles                 = $this->skautIsService->getUserRoles($skautIsUserId);
        $skautIsRoleSelectedId        = $this->skautIsService->getUserRoleId();
        $skautIsRoleSelected          = array_filter($skautIsRoles, static fn (stdClass $r) => $r->ID === $skautIsRoleSelectedId);
        $this->template->skautIsRoles = $skautIsRoles;
        if (empty($skautIsRoleSelected)) {
            $this->template->skautIsRoleSelected = null;
        } else {
            $this->template->skautIsRoleSelected = $skautIsRoleSelected[array_keys($skautIsRoleSelected)[0]];
        }
    }

    /**
     * Kontroluje oprávnění uživatele a případně jej přesměruje.
     *
     * @throws AbortException
     */
    public function checkPermission(string $permission): void
    {
        if (! $this->user->isAllowed($this->resource, $permission)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(':Admin:Dashboard:default');
        }
    }

    /** @throws AbortException */
    public function handleChangeRole(int $roleId): void
    {
        $this->skautIsService->updateUserRole($roleId);
        $this->redirect('this');
    }
}
