<?php

namespace App\AdminModule\Presenters;

use App\Presenters\BasePresenter;

abstract class AdminBasePresenter extends BasePresenter
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

        if (!$this->checkInstallationStatus())
            $this->redirect(':Install:Install:default');

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

    }
}