<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Services\AclService;
use App\Services\Authenticator;
use App\Services\QueryBus;
use App\WebModule\Forms\ApplicationFormFactory;
use App\WebModule\Presenters\WebBasePresenter;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

use function assert;

/**
 * Komponenta obsahu s přihláškou.
 */
class ApplicationContentControl extends BaseContentControl
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly ApplicationFormFactory $applicationFormFactory,
        private readonly Authenticator $authenticator,
        private readonly AclService $aclService,
        private readonly RoleRepository $roleRepository,
        private readonly SubeventRepository $subeventRepository,
        public IApplicationsGridControlFactory $applicationsGridControlFactory,
        public CustomInputRepository $customInputRepository,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function render(ContentDto|null $content = null): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_content.latte');

        if ($content) {
            $template->heading = $content->getHeading();
        }

        $presenter = $this->getPresenter();
        assert($presenter instanceof WebBasePresenter);

        $template->backlink = $presenter->getHttpRequest()->getUrl()->getPath();

        $user                = $presenter->getUser();
        $template->guestRole = $user->isInRole($this->aclService->findRoleNameBySystemName(Role::GUEST));
        $template->testRole  = Role::TEST;

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        if ($user->isLoggedIn()) {
            $dbUser              = $presenter->getDbUser();
            $userHasFixedFeeRole = $dbUser->hasFixedFeeRole();

            $template->isInUnapprovedRole    = $user->isInRole($this->aclService->findRoleNameBySystemName(Role::UNAPPROVED));
            $template->isInNonregisteredRole = $user->isInRole($this->aclService->findRoleNameBySystemName(Role::NONREGISTERED));
            $template->noRegisterableRole    = $this->roleRepository->findFilteredRoles(true, false, false)->isEmpty();
            $template->registrationStart     = $this->roleRepository->getRegistrationStart();
            $template->registrationEnd       = $this->roleRepository->getRegistrationEnd();
            $template->bankAccount           = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
            $template->dbUser                = $dbUser;
            $template->userHasFixedFeeRole   = $userHasFixedFeeRole;

            $template->usersApplications = $explicitSubeventsExists && $userHasFixedFeeRole
                ? $dbUser->getNotCanceledApplications()
                : ($explicitSubeventsExists
                    ? $dbUser->getNotCanceledSubeventsApplications()
                    : $dbUser->getNotCanceledRolesApplications()
                );
        }

        $template->explicitSubeventsExists = $explicitSubeventsExists;

        $template->render();
    }

    public function renderScripts(): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_content_scripts.latte');
        $template->render();
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    protected function createComponentApplicationForm(): Form
    {
        $p = $this->getPresenter();
        assert($p instanceof WebBasePresenter);

        $form = $this->applicationFormFactory->create($p->getDbUser());

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($p): void {
            $p->flashMessage('web.application_content.register_successful', 'success');

            $this->authenticator->updateRoles($p->getUser());

            $p->redirect('this');
        };

        $this->applicationFormFactory->onSkautIsError[] = static function () use ($p): void {
            $p->flashMessage('web.application_content.register_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid(): ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
