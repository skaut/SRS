<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\User\Commands\RegisterTroop;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\Authenticator;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use App\WebModule\Forms\GroupMembersForm;
use App\WebModule\Forms\IGroupMembersFormFactory;
use Doctrine\ORM\NonUniqueResultException;
use stdClass;
use Throwable;

use function array_filter;
use function array_keys;

/**
 * Komponenta s přihláškou oddílu.
 */
class TroopApplicationContentControl extends BaseContentControl
{
    private static array $ALLOWED_ROLE_TYPES = ['vedouciStredisko', 'vedouciOddil', 'cinovnikStredisko', 'cinovnikOddil'];

    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
        private Authenticator $authenticator,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private SkautIsService $skautIsService,
        private IGroupMembersFormFactory $groupMembersFormFactory
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

        $step           = $this->getPresenter()->getParameter('step');
        $template->step = $step;

        if ($user->isLoggedIn()) {
            $dbuser           = $this->userRepository->findById($user->id);
            $template->dbuser = $dbuser;

            $skautIsUserId          = $dbuser->getSkautISUserId();
            $skautIsRoles           = $this->skautIsService->getUserRoles($skautIsUserId, self::$ALLOWED_ROLE_TYPES);
            $template->skautIsRoles = $skautIsRoles;

            $troop = $this->queryBus->handle(new TroopByLeaderQuery($dbuser->getId()));
            if ($troop == null) {
                $this->commandBus->handle(new RegisterTroop($dbuser));
                $troop = $this->queryBus->handle(new TroopByLeaderQuery($dbuser->getId()));
            }

            $template->troop = $troop;

            if ($step === 'members') {
                // nacist existujici draft pro patrol nebo troop nebo podle patrolId
            } elseif ($step === 'additional_info') {
                // nacist existujici draft pro patrol nebo troop nebo podle patrolId
            } elseif ($step === 'confirm') {
                // nacist existujici draft pro patrol nebo troop nebo podle patrolId
            } else {
                $skautIsRoleSelectedId = $this->skautIsService->getUserRoleId();
                $skautIsRoleSelected   = array_filter($skautIsRoles, static fn (stdClass $r) => $r->ID === $skautIsRoleSelectedId);
                if (empty($skautIsRoleSelected)) {
                    $template->skautIsRoleSelected = null;
                } else {
                    $template->skautIsRoleSelected = $skautIsRoleSelected[array_keys($skautIsRoleSelected)[0]];
                }
            }
        }

        $template->render();
    }

    public function renderScripts(): void
    {
    }

    protected function createComponentGroupMembersForm(): GroupMembersForm
    {
        $type     = $this->getPresenter()->getParameter('type');
        $patrolId = $this->getPresenter()->getParameter('patrol_id');

        return $this->groupMembersFormFactory->create($type, $patrolId);
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
