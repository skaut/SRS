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
use App\Services\SkautIsService;
use App\WebModule\Forms\ApplicationFormFactory;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Komponenta s přihláškou oddílu.
 */
class TroopApplicationContentControl extends BaseContentControl
{
    public function __construct(
        private QueryBus $queryBus,
        private Authenticator $authenticator,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private SkautIsService $skautIsService,
        public CustomInputRepository $customInputRepository
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function render(?ContentDto $content = null): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/troop_application_content.latte');

        if ($content) {
            $template->heading = $content->getHeading();
        }

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->testRole  = Role::TEST;

        if ($user->isLoggedIn()) {
            $dbuser              = $this->userRepository->findById($user->id);
            $template->dbuser              = $dbuser;

            $skautIsUserId                = $dbuser->getSkautISUserId();
            $skautIsRoles                 = $this->skautIsService->getUserRoles($skautIsUserId);
            $skautIsRoleSelectedId        = $this->skautIsService->getUserRoleId();
            $skautIsRoleSelected          = array_filter($skautIsRoles, static fn (stdClass $r) => $r->ID === $skautIsRoleSelectedId);
            $this->template->skautIsRoles = $skautIsRoles;
            if (empty($skautIsRoleSelected)) {
                $this->template->skautIsRoleSelected = null;
            } else {
                $this->template->skautIsRoleSelected = $skautIsRoleSelected[array_keys($skautIsRoleSelected)[0]];
            }
        }

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
//    protected function createComponentApplicationForm(): Form
//    {
//        $form = $this->applicationFormFactory->create($this->getPresenter()->user->id);

//        $form->onSuccess[] = function (Form $form, stdClass $values): void {
//            $this->getPresenter()->flashMessage('web.application_content.register_successful', 'success');
//
//            $this->authenticator->updateRoles($this->getPresenter()->user);
//
//            $this->getPresenter()->redirect('this');
//        };

//        $this->applicationFormFactory->onSkautIsError[] = function (): void {
//            $this->getPresenter()->flashMessage('web.application_content.register_synchronization_failed', 'danger');
//        };
//
//        return $form;
//    }

//    protected function createComponentApplicationsGrid(): ApplicationsGridControl
//    {
//        return $this->applicationsGridControlFactory->create();
//    }

    public function handleChangeRole(int $roleId): void
    {
        $this->skautIsService->updateUserRole($roleId);
        $this->redirect('this');
    }
}
