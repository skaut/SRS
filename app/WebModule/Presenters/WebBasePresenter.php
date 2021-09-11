<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

use App\Model\Acl\Permission;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Presenters\BasePresenter;
use App\Services\Authenticator;
use App\Services\Authorizator;
use App\Services\CmsService;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use Nette\Application\AbortException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

/**
 * BasePresenter pro WebModule.
 */
abstract class WebBasePresenter extends BasePresenter
{
    /** @inject */
    public QueryBus $queryBus;

    /** @inject */
    public Authorizator $authorizator;

    /** @inject */
    public Authenticator $authenticator;

    /** @inject */
    public RoleRepository $roleRepository;

    /** @inject */
    public CmsService $cmsService;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public SkautIsService $skautIsService;

    protected ?User $dbuser = null;

    /**
     * Načte css podle konfigurace v common.neon.
     */
    protected function createComponentCss(): CssLoader
    {
        return $this->webLoader->createCssLoader('web');
    }

    /**
     * Načte javascript podle konfigurace v common.neon.
     */
    protected function createComponentJs(): JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('web');
    }

    /**
     * @throws AbortException
     * @throws Throwable
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkInstallation();

        if ($this->user->isLoggedIn() && ! $this->skautIsService->isLoggedIn()) {
            $this->user->logout(true);
        }

        $this->user->setAuthorizator($this->authorizator);

        $this->dbuser = $this->user->isLoggedIn() ? $this->userRepository->findById($this->user->id) : null;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo        = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $this->template->footer      = $this->queryBus->handle(new SettingStringValueQuery(Settings::FOOTER));
        $this->template->seminarName = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $this->template->gaId        = $this->queryBus->handle(new SettingStringValueQuery(Settings::GA_ID));

        $this->template->nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        $this->template->unapprovedRole    = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
        $this->template->testRole          = Role::TEST;

        $this->template->adminAccess = $this->user->isAllowed(SrsResource::ADMIN, Permission::ACCESS);

        $this->template->pages = $this->cmsService->findPublishedOrderedByPositionDto();
    }

    /**
     * Ukončí testování role.
     *
     * @throws AbortException
     */
    public function actionExitRoleTest(): void
    {
        $this->authenticator->updateRoles($this->user);
        $this->redirect(':Admin:Acl:default');
    }

    /**
     * Zkontroluje stav instalace.
     *
     * @throws AbortException
     * @throws Throwable
     */
    private function checkInstallation(): void
    {
        try {
            if (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::ADMIN_CREATED))) {
                $this->redirect(':Install:Install:default');
            }
        } catch (HandlerFailedException $ex) {
            $this->redirect(':Install:Install:default');
        }
    }
}
