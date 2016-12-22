<?php

namespace App\WebModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;

abstract class WebBasePresenter extends BasePresenter
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
        return $this->webLoader->createCssLoader('web');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('web');
    }

    public function startup()
    {
        parent::startup();

        $this->checkInstallation();

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo = $this->settingsRepository->getValue('logo');
        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->seminarName = $this->settingsRepository->getValue('seminar_name');

        $this->template->dbuser = $this->user->identity->dbuser;

        $this->template->adminAccess = $this->user->isAllowed(Resource::ADMIN, Permission::ACCESS);
        $this->template->displayUsersRoles = $this->settingsRepository->getValue('display_users_roles');

        $this->template->pages = $this->pageRepository->findPublishedPagesOrderedByPosition();
        $this->template->sidebarVisibility = false;

        $this->template->settings = $this->settingsRepository;
    }

    private function checkInstallation() {
        try {
            if (!filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN))
                $this->redirect(':Install:Install:default');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
            $this->redirect(':Install:Install:default');
        } catch (\App\Model\Settings\SettingsException $ex) {
            $this->redirect(':Install:Install:default');
        }
    }
}