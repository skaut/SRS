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

        $user               = $presenter->user;
        $template->testRole = Role::TEST;

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        if ($user->isLoggedIn()) {
            $dbUser              = $presenter->getDbUser();
            $userHasFixedFeeRole = $dbUser->hasFixedFeeRole();

            $template->guestRole           = false;
            $template->unapprovedRole      = $user->isInRole($this->roleRepository->findBySystemName(Role::UNAPPROVED)->getName());
            $template->nonregisteredRole   = $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)->getName());
            $template->noRegisterableRole  = $this->roleRepository->findFilteredRoles(true, false, false)->isEmpty();
            $template->registrationStart   = $this->roleRepository->getRegistrationStart();
            $template->registrationEnd     = $this->roleRepository->getRegistrationEnd();
            $template->bankAccount         = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
            $template->dbUser              = $dbUser;
            $template->userHasFixedFeeRole = $userHasFixedFeeRole;

            $template->usersApplications = $explicitSubeventsExists && $userHasFixedFeeRole
                ? $dbUser->getNotCanceledApplications()
                : ($explicitSubeventsExists
                    ? $dbUser->getNotCanceledSubeventsApplications()
                    : $dbUser->getNotCanceledRolesApplications()
                );
        } else {
            $template->guestRole = true;
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
        $form = $this->applicationFormFactory->create($this->getPresenter()->user->id);

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->getPresenter()->flashMessage('web.application_content.register_successful', 'success');

            $this->authenticator->updateRoles($this->getPresenter()->user);

            $this->getPresenter()->redirect('this');
        };

        $this->applicationFormFactory->onSkautIsError[] = function (): void {
            $this->getPresenter()->flashMessage('web.application_content.register_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid(): ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }
}
