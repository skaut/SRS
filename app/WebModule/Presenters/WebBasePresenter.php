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
use Nette\DI\Attributes\Inject;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

/**
 * BasePresenter pro WebModule.
 */
abstract class WebBasePresenter extends BasePresenter
{
    #[Inject]
    public QueryBus $queryBus;

    #[Inject]
    public Authorizator $authorizator;

    #[Inject]
    public Authenticator $authenticator;

    #[Inject]
    public RoleRepository $roleRepository;

    #[Inject]
    public CmsService $cmsService;

    #[Inject]
    public UserRepository $userRepository;

    #[Inject]
    public SkautIsService $skautIsService;

    protected User|null $dbUser = null;

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

        $this->dbUser = $this->user->isLoggedIn() ? $this->userRepository->findById($this->user->id) : null;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->dbUser = $this->dbUser;

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo         = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $this->template->footer       = $this->queryBus->handle(new SettingStringValueQuery(Settings::FOOTER));
        $this->template->seminarName  = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $this->template->trackingCode = $this->queryBus->handle(new SettingStringValueQuery(Settings::TRACKING_CODE));

        $this->template->nonregisteredRole = Role::NONREGISTERED;
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

    public function getDbUser(): User|null
    {
        return $this->dbUser;
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
        } catch (HandlerFailedException) {
            $this->redirect(':Install:Install:default');
        }
    }
}
