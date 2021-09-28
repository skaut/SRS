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
use App\Model\User\Repositories\UserRepository;
use App\Services\Authenticator;
use App\Services\QueryBus;
use App\WebModule\Forms\ApplicationFormFactory;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Komponenta s přihláškou.
 */
class ApplicationContentControl extends BaseContentControl
{
    private QueryBus $queryBus;

    private ApplicationFormFactory $applicationFormFactory;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private Authenticator $authenticator;

    private SubeventRepository $subeventRepository;

    public IApplicationsGridControlFactory $applicationsGridControlFactory;

    public CustomInputRepository $customInputRepository;

    public function __construct(
        QueryBus $queryBus,
        ApplicationFormFactory $applicationFormFactory,
        Authenticator $authenticator,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository,
        IApplicationsGridControlFactory $applicationsGridControlFactory,
        CustomInputRepository $customInputRepository
    ) {
        $this->queryBus                       = $queryBus;
        $this->applicationFormFactory         = $applicationFormFactory;
        $this->authenticator                  = $authenticator;
        $this->userRepository                 = $userRepository;
        $this->roleRepository                 = $roleRepository;
        $this->subeventRepository             = $subeventRepository;
        $this->applicationsGridControlFactory = $applicationsGridControlFactory;
        $this->customInputRepository          = $customInputRepository;
    }

    /**
     * @throws NonUniqueResultException
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function render(?ContentDto $content = null): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_content.latte');

        if ($content) {
            $template->heading = $content->getHeading();
        }

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->testRole  = Role::TEST;

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        if ($user->isLoggedIn()) {
            $dbuser              = $this->userRepository->findById($user->id);
            $userHasFixedFeeRole = $dbuser->hasFixedFeeRole();

            $template->unapprovedRole      = $user->isInRole($this->roleRepository->findBySystemName(Role::UNAPPROVED)->getName());
            $template->nonregisteredRole   = $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)->getName());
            $template->noRegisterableRole  = $this->roleRepository->findFilteredRoles(true, false, false)->isEmpty();
            $template->registrationStart   = $this->roleRepository->getRegistrationStart();
            $template->registrationEnd     = $this->roleRepository->getRegistrationEnd();
            $template->bankAccount         = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
            $template->dbuser              = $dbuser;
            $template->userHasFixedFeeRole = $userHasFixedFeeRole;

            $template->usersApplications = $explicitSubeventsExists && $userHasFixedFeeRole
                ? $dbuser->getNotCanceledApplications()
                : ($explicitSubeventsExists
                    ? $dbuser->getNotCanceledSubeventsApplications()
                    : $dbuser->getNotCanceledRolesApplications()
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
