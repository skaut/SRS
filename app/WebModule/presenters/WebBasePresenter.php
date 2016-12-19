<?php

namespace App\WebModule\Presenters;

use App\Presenters\BasePresenter;
use App\Services\Authorizator;

abstract class WebBasePresenter extends BasePresenter
{
    /**
     * @var \App\Model\Settings\SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var \App\Model\CMS\PageRepository
     * @inject
     */
    public $pageRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var \App\Model\ACL\ResourceRepository
     * @inject
     */
    public $resourceRepository;

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

        if (!$this->checkInstallationStatus())
            $this->redirect(':Install:Install:default');

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();
        $this->template->logo = $this->settingsRepository->getValue('logo');
        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->title = $this->settingsRepository->getValue('seminar_name');
        $this->template->sidebar = false;
        $this->template->displayUsersRoles = $this->settingsRepository->getValue('display_users_roles');
        if (isset($this->params['pageId']) && $this->params['pageId'] !== null)
            $this->template->slug = $this->pageRepository->idToSlug($this->params['pageId']);
    }
}