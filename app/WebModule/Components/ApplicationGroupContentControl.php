<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Group\Repositories\GroupRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\ApplicationGroupService;
use App\Services\Authenticator;
use App\Services\QueryBus;
use App\WebModule\Forms\ApplicationGroupFormFactory;
use App\WebModule\Presenters\WebBasePresenter;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

use function assert;
use function count;
use function intval;

/**
 * Komponenta obsahu s přihláškou.
 */
class ApplicationGroupContentControl extends BaseContentControl
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly ApplicationGroupFormFactory $applicationGroupFormFactory,
        private readonly Authenticator $authenticator,
        private readonly RoleRepository $roleRepository,
        private readonly GroupRepository $groupRepository,
        private readonly UserRepository $userRepository,
        private readonly SubeventRepository $subeventRepository,
        public IGroupUsersGridControlFactory $groupUsersGridControlFactory,
        public CustomInputRepository $customInputRepository,
        private readonly ApplicationGroupService $applicationGroupService,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function render(ContentDto|null $content = null): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/application_group_content.latte');

        if ($content) {
            $template->heading = $content->getHeading();
        }

        $presenter = $this->getPresenter();
        assert($presenter instanceof WebBasePresenter);

        $template->backlink = $presenter->getHttpRequest()->getUrl()->getPath();

        $user                = $presenter->getUser();
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->testRole  = Role::TEST;

        $explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        if ($user->isLoggedIn()) {
            $dbUser              = $presenter->getDbUser();
            $userHasFixedFeeRole = $dbUser->hasFixedFeeRole();
            $userGroupId         = $dbUser->getGroupId();
            $group               = $this->groupRepository->findById($userGroupId);
            $groupLeaderId       = $group->getLeaderId();
            $groupLeader         = $this->userRepository->findById($groupLeaderId);
            $groupLeaderName     = $groupLeader->getFirstName() . ' ' . $groupLeader->getLastName();
            $groupUsersArr       = $this->userRepository->findAllInGroup($userGroupId);
            $groupUsersCount     = count($groupUsersArr);

            $template->unapprovedRole      = $user->isInRole($this->roleRepository->findBySystemName(Role::UNAPPROVED)->getName());
            $template->nonregisteredRole   = $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)->getName());
            $template->groupLeaderRole     = $user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_LEADER)->getName());
            $template->groupMemberRole     = $user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_MEMBER)->getName());
            $template->noRegisterableRole  = $this->roleRepository->findFilteredRoles(true, false, false)->isEmpty();
            $template->registrationStart   = $this->roleRepository->getRegistrationStart();
            $template->registrationEnd     = $this->roleRepository->getRegistrationEnd();
            $template->bankAccount         = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
            $template->dbUser              = $dbUser;
            $template->group               = $group;
            $template->groupLeaderName     = $groupLeaderName;
            $template->groupUsersCount     = $groupUsersCount;
            $template->userHasFixedFeeRole = $userHasFixedFeeRole;

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
        $template->setFile(__DIR__ . '/templates/application_group_content_scripts.latte');
        $template->render();
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    protected function createComponentApplicationGroupForm(): Form
    {
        $p = $this->getPresenter();
        assert($p instanceof WebBasePresenter);

        $form = $this->applicationGroupFormFactory->create($p->getDbUser());

        $form->onSuccess[] = function (Form $form, stdClass $values) use ($p): void {
            $p->flashMessage('web.application_content.register_successful', 'success');

            $this->authenticator->updateRoles($p->getUser());

            $p->redirect('this');
        };

        $this->applicationGroupFormFactory->onSkautIsError[] = static function () use ($p): void {
            $p->flashMessage('web.application_content.register_synchronization_failed', 'danger');
        };

        return $form;
    }

    protected function createComponentApplicationsGrid(): ApplicationsGridControl
    {
        return $this->applicationsGridControlFactory->create();
    }

    protected function createComponentGroupUsersGrid(): GroupUsersGridControl
    {
        return $this->groupUsersGridControlFactory->create();
    }

    public function handleRemoveUserFromGroup($id): void
    {
        $p = $this->getPresenter();

        $removeUser  = $this->userRepository->findById(intval($id));
        $userGroupId = $removeUser->setGroupId(0);
        $this->userRepository->save($removeUser);

        if ($user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_MEMBER)->getName())) {
            $userRoleUnregistered = $this->roleRepository->findById(2);
            $removeUser->addRole($userRoleUnregistered);
            $userRoleMember = $this->roleRepository->findById(10);
            $removeUser->removeRole($userRoleMember);
        }

        $p->redirect('this');
    }

    public function handleRemoveGroup($id): void
    {
        $p = $this->getPresenter();

        $groupUsersArr = $this->userRepository->findAllInGroup(intval($groupId));
        foreach ($groupUsersArr as $groupUser) {
            $userGroupId = $groupUser->setGroupId(0);
            $this->userRepository->save($removeUser);

            if ($user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_MEMBER)->getName())) {
                $userRoleUnregistered = $this->roleRepository->findById(2);
                $groupUser->addRole($userRoleUnregistered);
                $userRoleMember = $this->roleRepository->findById(10);
                $groupUser->removeRole($userRoleMember);
            }

            if ($user->isInRole($this->roleRepository->findBySystemName(Role::GROUP_LEADER)->getName())) {
                $userRoleUnregistered = $this->roleRepository->findById(2);
                $groupUser->addRole($userRoleUnregistered);
                $userRoleLeader = $this->roleRepository->findById(9);
                $groupUser->removeRole($userRoleLeader);
            }
        }

        $removeGroup = $this->groupRepository->findById(intval($id));
        $this->groupRepository->remove($group);

        $p->redirect('this');
    }

    public function handleCloseGroup($id): void
    {
        $p = $this->getPresenter();

        $group = $this->groupRepository->findById(intval($id));

/*
        $groupUsersArr = $this->userRepository->findAllInGroup(intval($groupId));
        $groupUsersCount = count($groupUsersArr);

        $userRoleLeader = $this->roleRepository->findById(9);
        $roleFee = $userRoleLeader->getFee();

        $totalGroupFee = $groupUsersCount*$roleFee;
*/
        $this->applicationGroupService->register_group($presenter->getDbUser(), 9, $presenter->getDbUser(), $group);

        $p->redirect('this');
    }
}
