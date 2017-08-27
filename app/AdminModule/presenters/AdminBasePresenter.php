<?php

namespace App\AdminModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\ResourceRepository;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use App\Services\SkautIsService;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;


/**
 * BasePresenter pro AdminModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class AdminBasePresenter extends BasePresenter
{
    /**
     * @var Authorizator
     * @inject
     */
    public $authorizator;

    /**
     * @var ResourceRepository
     * @inject
     */
    public $resourceRepository;

    /**
     * @var RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

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
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('admin');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('admin');
    }

    public function startup()
    {
        parent::startup();

        if ($this->user->isLoggedIn() && !$this->skautIsService->isLoggedIn())
            $this->user->logout(TRUE);

        $this->user->setAuthorizator($this->authorizator);

        if (!$this->user->isLoggedIn()) {
            $this->redirect(':Auth:login', ['backlink' => $this->getHttpRequest()->getUrl()->getPath()]);
        }
        if (!$this->user->isAllowed(Resource::ADMIN, Permission::ACCESS)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(':Web:Page:default');
        }

        $this->dbuser = $this->userRepository->findById($this->user->id);
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->resourceACL = Resource::ACL;
        $this->template->resourceCMS = Resource::CMS;
        $this->template->resourceConfiguration = Resource::CONFIGURATION;
        $this->template->resourceUsers = Resource::USERS;
        $this->template->resourceMailing = Resource::MAILING;
        $this->template->resourceProgram = Resource::PROGRAM;
        $this->template->resourceStructure = Resource::STRUCTURE;

        $this->template->permissionAccess = Permission::ACCESS;
        $this->template->permissionManage = Permission::MANAGE;
        $this->template->permissionManageOwnPrograms = Permission::MANAGE_OWN_PROGRAMS;
        $this->template->permissionManageAllPrograms = Permission::MANAGE_ALL_PROGRAMS;
        $this->template->permissionManageSchedule = Permission::MANAGE_SCHEDULE;
        $this->template->permissionManageRooms = Permission::MANAGE_ROOMS;
        $this->template->permissionManageCategories = Permission::MANAGE_CATEGORIES;

        $this->template->footer = $this->settingsRepository->getValue(Settings::FOOTER);
        $this->template->seminarName = $this->settingsRepository->getValue(Settings::SEMINAR_NAME);

        $this->template->sidebarVisible = FALSE;

        $this->template->settings = $this->settingsRepository;

        $this->template->containerAttributes = '';
    }

    /**
     * Kontroluje oprávnění uživatele a případně jej přesměruje.
     * @param $permission
     */
    public function checkPermission($permission)
    {
        if (!$this->user->isAllowed($this->resource, $permission)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(':Admin:Dashboard:default');
        }
    }
}
