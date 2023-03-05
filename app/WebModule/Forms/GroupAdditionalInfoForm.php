<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Role;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Queries\PatrolByTroopAndNotConfirmedQuery;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\UserGroupRole;
use App\Services\QueryBus;
use Collator;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function usort;

/**
 * Komponenta s formulářem pro vyplnění doplňujících údajů o členech družiny.
 */
class GroupAdditionalInfoForm extends UI\Control
{
    /** @var UserGroupRole[] */
    private array $usersRoles;
    private bool $attendeesCountError    = false;
    private bool $groupLeadersCountError = false;
    private bool $leadersCountError      = false;
    private bool $escortsCountError      = false;

    /**
     * Událost při úspěšném odeslání formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    public function __construct(
        private string $type,
        private ?int $patrolId,
        private BaseFormFactory $baseFormFactory,
        private QueryBus $queryBus,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/group_additional_info_form.latte');

        $this->resolveUsersRoles();

        $this->template->type       = $this->type;
        $this->template->patrolId   = $this->patrolId;
        $this->template->usersRoles = $this->usersRoles;

        $this->template->attendeesCountError    = $this->attendeesCountError;
        $this->template->groupLeadersCountError = $this->groupLeadersCountError;
        $this->template->leadersCountError      = $this->leadersCountError;
        $this->template->escortsCountError      = $this->escortsCountError;

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $this->resolveUsersRoles();

        $form = $this->baseFormFactory->create();

        foreach ($this->usersRoles as $userRole) {
            $form->addTextArea('health_info_' . $userRole->getUser()->getId(), null, null, 3)
                ->setDefaultValue($userRole->getUser()->getHealthInfo());
        }

        $form->addSubmit('submit', 'Pokračovat')->setDisabled($this->attendeesCountError || $this->groupLeadersCountError || $this->leadersCountError || $this->escortsCountError);

        $form->setAction($this->getPresenter()->link('this', ['step' => 'additional_info', 'type' => $this->type, 'patrol_id' => $this->patrolId]));

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        foreach ($this->usersRoles as $userRole) {
            $user                = $userRole->getUser();
            $healthInfoInputName = 'health_info_' . $user->getId();
            $user->setHealthInfo($values->$healthInfoInputName);
            $this->userRepository->save($user);
        }

        $this->onSave();
    }

    private function resolveUsersRoles(): void
    {
        $user  = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));
        $troop = $this->queryBus->handle(new TroopByLeaderQuery($user->getId()));

        if ($this->type == 'patrol') {
            if ($this->patrolId !== null) {
                $patrol           = $this->queryBus->handle(new PatrolByIdQuery($this->patrolId));
                $this->usersRoles = $patrol->getUsersRoles()->toArray();
            } else {
                $patrol           = $this->queryBus->handle(new PatrolByTroopAndNotConfirmedQuery($troop->getId()));
                $this->patrolId   = $patrol->getId();
                $this->usersRoles = $patrol->getUsersRoles()->toArray();
            }

            $attendeesCount               = $patrol->countUsersInRoles([Role::ATTENDEE, Role::PATROL_LEADER]);
            $this->attendeesCountError    = $attendeesCount < 4 || $attendeesCount > 12;
            $this->groupLeadersCountError = $patrol->countUsersInRoles([Role::PATROL_LEADER]) > 1;
            $this->leadersCountError      = $patrol->countUsersInRoles([Role::LEADER]) != 1;
        } elseif ($this->type === 'troop') {
            $this->usersRoles        = $troop->getUsersRoles()->toArray();
            $this->escortsCountError = $troop->countUsersInRoles([Role::ESCORT]) > $troop->getMaxEscortsCount();
        }

        $collator = new Collator('cs_CZ');
        usort($this->usersRoles, static fn ($a, $b) => $collator->compare($a->getUser()->getDisplayName(), $b->getUser()->getDisplayName()));
    }
}
