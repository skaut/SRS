<?php

namespace App\WebModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;

abstract class WebBasePresenter extends BasePresenter
{
    /**
     * @var \App\Model\Settings\SettingsRepository
     */
    protected $settingsRepository;

    /**
     * @var \App\Model\CMS\PageRepository
     */
    protected $pageRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     */
    protected $roleRepository;

    /**
     * @var \App\Model\ACL\ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @var \App\Model\User\User
     */
    protected $userRepository;

    /**
     * @var \App\Model\Program\Program
     */
    protected $programRepository;

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

        $this->loadRepositories();

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo = $this->settingsRepository->getValue('logo');
        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->title = $this->settingsRepository->getValue('seminar_name');

        $this->template->sidebar = false;

        $this->template->adminAccess = $this->user->isAllowed(Resource::ADMIN, Permission::ACCESS);
        $this->template->displayUsersRoles = $this->settingsRepository->getValue('display_users_roles');

        $this->template->pages = $this->pageRepository->findPublishedPagesOrderedByPosition();
        if (isset($this->params['pageId']) && $this->params['pageId'] !== null)
            $this->template->slug = $this->pageRepository->idToSlug($this->params['pageId']);
    }

    private function loadRepositories()
    {
        $this->settingsRepository = $this->em->getRepository(\App\Model\Settings\Settings::class);
        $this->pageRepository = $this->em->getRepository(\App\Model\CMS\Page::class);
        $this->roleRepository = $this->em->getRepository(\App\Model\ACL\Role::class);
        $this->resourceRepository = $this->em->getRepository(\App\Model\ACL\Resource::class);
        $this->userRepository = $this->em->getRepository(\App\Model\User\User::class);
        $this->prograRepository = $this->em->getRepository(\App\Model\Program\Program::class);
    }
}