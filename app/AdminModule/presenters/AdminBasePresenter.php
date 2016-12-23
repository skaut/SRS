<?php

namespace App\AdminModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

abstract class AdminBasePresenter extends BasePresenter
{
    /**
     * @var \App\Model\ACL\ResourceRepository
     * @inject
     */
    public $resourceRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var \App\Model\CMS\PageRepository
     * @inject
     */
    public $pageRepository;

    /**
     * @var \App\Model\Settings\SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('admin');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('admin');
    }

    public function startup()
    {
        parent::startup();

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

        if (!$this->user->isLoggedIn() || !$this->user->isAllowed(Resource::ADMIN, Permission::ACCESS))
            $this->redirect(":Web:Page:default");

        $this->template->resourceACL = Resource::ACL;
        $this->template->resourceCMS = Resource::CMS;
        $this->template->resourceConfiguration = Resource::CONFIGURATION;
        $this->template->resourceUsers = Resource::USERS;
        $this->template->resourceMailing = Resource::MAILING;
        $this->template->resourceProgram = Resource::PROGRAM;

        $this->template->permissionAccess = Permission::ACCESS;
        $this->template->permissionManage = Permission::MANAGE;
        $this->template->permissionManageOwnPrograms = Permission::MANAGE_OWN_PROGRAMS;
        $this->template->permissionManageAllPrograms = Permission::MANAGE_ALL_PROGRAMS;
        $this->template->permissionManageHarmonogram = Permission::MANAGE_HARMONOGRAM;
        $this->template->permissionManageRooms = Permission::MANAGE_ROOMS;
        $this->template->permissionManageCategories = Permission::MANAGE_CATEGORIES;

        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->seminarName = $this->settingsRepository->getValue('seminar_name');

        $this->template->sidebarVisibility = false;

        $this->template->settings = $this->settingsRepository;
    }
}