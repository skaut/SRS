<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\User\Commands\RegisterTroop;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use App\WebModule\Forms\GroupAdditionalInfoForm;
use App\WebModule\Forms\GroupConfirmForm;
use App\WebModule\Forms\GroupMembersForm;
use App\WebModule\Forms\IGroupAdditionalInfoFormFactory;
use App\WebModule\Forms\IGroupConfirmFormFactory;
use App\WebModule\Forms\IGroupMembersFormFactory;
use App\WebModule\Forms\ITroopConfirmFormFactory;
use App\WebModule\Forms\TroopConfirmForm;
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
    /** @var string[] */
    private static array $ALLOWED_ROLE_TYPES = ['vedouciStredisko', 'vedouciOddil', 'cinovnikStredisko', 'cinovnikOddil'];

    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private SkautIsService $skautIsService,
        private IGroupMembersFormFactory $groupMembersFormFactory,
        private IGroupAdditionalInfoFormFactory $groupAdditionalInfoFormFactory,
        private IGroupConfirmFormFactory $groupConfirmFormFactory,
        private ITroopConfirmFormFactory $troopConfirmFormFactory,
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

            if ($step === null) {
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
        if ($patrolId !== null) {
            $patrolId = (int) $patrolId;
        }

        $form = $this->groupMembersFormFactory->create($type, $patrolId);

        $form->onSave[] = function () use ($type, $patrolId): void {
            $this->getPresenter()->redirect('this', ['step' => 'additional_info', 'type' => $type, 'patrol_id' => $patrolId]);
        };

        return $form;
    }

    protected function createComponentGroupAdditionalInfoForm(): GroupAdditionalInfoForm
    {
        $type     = $this->getPresenter()->getParameter('type');
        $patrolId = $this->getPresenter()->getParameter('patrol_id');
        if ($patrolId !== null) {
            $patrolId = (int) $patrolId;
        }

        $form = $this->groupAdditionalInfoFormFactory->create($type, $patrolId);

        $form->onSave[] = function () use ($type, $patrolId): void {
            $this->getPresenter()->redirect('this', ['step' => 'confirm', 'type' => $type, 'patrol_id' => $patrolId]);
        };

        return $form;
    }

    protected function createComponentGroupConfirmForm(): GroupConfirmForm
    {
        $type     = $this->getPresenter()->getParameter('type');
        $patrolId = $this->getPresenter()->getParameter('patrol_id');
        if ($patrolId !== null) {
            $patrolId = (int) $patrolId;
        }

        $form = $this->groupConfirmFormFactory->create($type, $patrolId);

        $form->onSave[] = function (): void {
            $this->getPresenter()->redirect('this');
        };

        return $form;
    }

    protected function createComponentTroopConfirmForm(): TroopConfirmForm
    {
        $form = $this->troopConfirmFormFactory->create();

        $form->onSave[] = function (): void {
            $p = $this->getPresenter();
            $p->flashMessage('Přihláška skupiny byla úspěšně odeslána.', 'success');
            $p->redirect('this');
        };

        return $form;
    }

    public function handleChangeRole(int $roleId): void
    {
        $this->skautIsService->updateUserRole($roleId);
        $this->redirect('this');
    }
}
