<?php

namespace App\AdminModule\Presenters;

use App\Presenters\BasePresenter;

abstract class AdminBasePresenter extends BasePresenter
{
    /**
     * @var \App\Model\Settings\SettingsRepository
     * @inject
     */
    public $settingsRepository;

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