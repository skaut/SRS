<?php

namespace App\WebModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\User;
use App\Presenters\BasePresenter;
use App\Services\Authenticator;
use App\Services\Authorizator;
use App\Services\SkautIsService;
use Doctrine\DBAL\Exception\TableNotFoundException;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;


/**
 * BasePresenter pro WebModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class WebBasePresenter extends BasePresenter
{
    /**
     * @var Authorizator
     * @inject
     */
    public $authorizator;

    /**
     * @var Authenticator
     * @inject
     */
    public $authenticator;

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
     * @var \App\Model\User\UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SkautIsService
     * @inject
     */
    public $skautIsService;

    /** @var User */
    protected $dbuser;


    /**
     * Načte css podle konfigurace v config.neon.
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('web');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
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

        if ($this->user->isLoggedIn() && !$this->skautIsService->isLoggedIn())
            $this->user->logout(true);

        $this->user->setAuthorizator($this->authorizator);

        $this->dbuser = $this->user->isLoggedIn() ? $this->userRepository->findById($this->user->id) : null;
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo = $this->settingsRepository->getValue(Settings::LOGO);
        $this->template->footer = $this->settingsRepository->getValue(Settings::FOOTER);
        $this->template->seminarName = $this->settingsRepository->getValue(Settings::SEMINAR_NAME);

        $this->template->nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        $this->template->unapprovedRole = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
        $this->template->testRole = Role::TEST;

        $this->template->adminAccess = $this->user->isAllowed(Resource::ADMIN, Permission::ACCESS);
        $this->template->displayUsersRoles = $this->settingsRepository->getValue(Settings::DISPLAY_USERS_ROLES);

        $this->template->pages = $this->pageRepository->findPublishedOrderedByPosition();
        $this->template->sidebarVisible = false;

        $this->template->settings = $this->settingsRepository;
    }

    /**
     * Ukončí testování role.
     */
    public function actionExitRoleTest()
    {
        $this->authenticator->updateRoles($this->user);
        $this->redirect(':Admin:Acl:default');
    }

    /**
     * Zkontroluje stav instalace.
     */
    private function checkInstallation()
    {
        try {
            if (!filter_var($this->settingsRepository->getValue(Settings::ADMIN_CREATED), FILTER_VALIDATE_BOOLEAN))
                $this->redirect(':Install:Install:default');
        } catch (TableNotFoundException $ex) {
            $this->redirect(':Install:Install:default');
        } catch (SettingsException $ex) {
            $this->redirect(':Install:Install:default');
        }
    }
}