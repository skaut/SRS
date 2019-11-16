<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\ResourceFacade;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use App\Services\SkautIsService;
use Nette\Application\AbortException;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;
use function array_filter;
use function array_keys;

/**
 * BasePresenter pro AdminModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
abstract class AdminBasePresenter extends BasePresenter
{
    /**
     * @var Authorizator
     * @inject
     */
    public $authorizator;

    /** @var string */
    protected $resource = null;

    /**
     * @var ResourceFacade
     * @inject
     */
    public $resourceFacade;

    /**
     * @var RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var SettingsFacade
     * @inject
     */
    public $settingsFacade;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SkautIsService
     * @inject
     */
    public $skautIsService;

    /**
     * Přihlášený uživatel.
     * @var User
     */
    public $dbuser;


    /**
     * Načte css podle konfigurace v config.neon.
     */
    protected function createComponentCss() : CssLoader
    {
        return $this->webLoader->createCssLoader('admin');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
     */
    protected function createComponentJs() : JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('admin');
    }

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        if ($this->user->isLoggedIn() && ! $this->skautIsService->isLoggedIn()) {
            $this->user->logout(true);
        }

        $this->user->setAuthorizator($this->authorizator);

        if (! $this->user->isLoggedIn()) {
            $this->redirect(':Auth:login', ['backlink' => $this->getHttpRequest()->getUrl()->getPath()]);
        }
        if (! $this->user->isAllowed(Resource::ADMIN, Permission::ACCESS)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $this->dbuser = $this->userRepository->findById($this->user->id);
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->resourceACL           = Resource::ACL;
        $this->template->resourceCMS           = Resource::CMS;
        $this->template->resourceConfiguration = Resource::CONFIGURATION;
        $this->template->resourceUsers         = Resource::USERS;
        $this->template->resourcePayments      = Resource::PAYMENTS;
        $this->template->resourceMailing       = Resource::MAILING;
        $this->template->resourceProgram       = Resource::PROGRAM;

        $this->template->permissionAccess            = Permission::ACCESS;
        $this->template->permissionManage            = Permission::MANAGE;
        $this->template->permissionManageOwnPrograms = Permission::MANAGE_OWN_PROGRAMS;
        $this->template->permissionManageAllPrograms = Permission::MANAGE_ALL_PROGRAMS;
        $this->template->permissionManageSchedule    = Permission::MANAGE_SCHEDULE;
        $this->template->permissionManageRooms       = Permission::MANAGE_ROOMS;
        $this->template->permissionManageCategories  = Permission::MANAGE_CATEGORIES;
        $this->template->permissionChoosePrograms    = Permission::CHOOSE_PROGRAMS;

        $this->template->footer      = $this->settingsFacade->getValue(Settings::FOOTER);
        $this->template->seminarName = $this->settingsFacade->getValue(Settings::SEMINAR_NAME);

        $this->template->sidebarVisible = false;

        $this->template->settings = $this->settingsFacade;

        $this->template->containerAttributes = '';

        $skautIsUserId                = $this->dbuser->getSkautISUserId();
        $skautIsRoles                 = $this->skautIsService->getUserRoles($skautIsUserId);
        $skautIsRoleSelectedId        = $this->skautIsService->getUserRoleId();
        $skautIsRoleSelected          = array_filter($skautIsRoles, function ($r) use ($skautIsRoleSelectedId) {
            return $r->ID === $skautIsRoleSelectedId;
        });
        $this->template->skautIsRoles = $skautIsRoles;
        if (empty($skautIsRoleSelected)) {
            $this->template->skautIsRoleSelected = null;
        } else {
            $this->template->skautIsRoleSelected = $skautIsRoleSelected[array_keys($skautIsRoleSelected)[0]];
        }
    }

    /**
     * Kontroluje oprávnění uživatele a případně jej přesměruje.
     * @throws AbortException
     */
    public function checkPermission(string $permission) : void
    {
        if ($this->user->isAllowed($this->resource, $permission)) {
            return;
        }

        $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
        $this->redirect(':Admin:Dashboard:default');
    }

    /**
     * @throws AbortException
     */
    public function handleChangeRole(int $roleId) : void
    {
        $this->skautIsService->updateUserRole($roleId);
        $this->redirect('this');
    }
}
