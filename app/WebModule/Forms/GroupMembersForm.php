<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Queries\RolesByTypeQuery;
use App\Model\Acl\Role;
use App\Model\Enums\TroopApplicationState;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\UpdateGroupMembers;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Queries\PatrolByTroopAndNotConfirmedQuery;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\Troop;
use App\Model\User\UserGroupRole;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use Collator;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function count;
use function sprintf;
use function usort;

/**
 * Komponenta s formulářem pro výběr členů družiny.
 */
class GroupMembersForm extends UI\Control
{
    /** @var string[] */
    private static array $ALLOWED_UNIT_TYPES = ['oddil'];

    private ?Troop $troop = null;

    /** @var stdClass[] */
    private array $units;

    /** @var stdClass[][] */
    private array $members;

    private DateTimeImmutable $seminarStart;

    /**
     * Událost při úspěšném odeslání formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    /**
     * Událost při neúspěšném odeslání formuláře.
     *
     * @var callable[]
     */
    public array $onError = [];

    public array $onRemoveAll = [];

    private string $patrolName = '';

    /** @var Collection<int, UserGroupRole> */
    private Collection $usersRoles;

    public function __construct(
        private string $type,
        private ?int $patrolId,
        private BaseFormFactory $baseFormFactory,
        private QueryBus $queryBus,
        private CommandBus $commandBus,
        private SkautIsService $skautIsService
    ) {
        $this->seminarStart = $this->queryBus->handle(new SettingDateValueQuery(Settings::SEMINAR_FROM_DATE));

        $this->units   = $this->skautIsService->getUnitAllUnit(self::$ALLOWED_UNIT_TYPES);
        $this->members = [];

        $collator = new Collator('cs_CZ');
        foreach ($this->units as $unit) {
            $unitMembers = $this->skautIsService->getMembershipAll($unit->ID, $this->type === 'troop' ? 18 : null, $this->seminarStart);
            usort($unitMembers, static fn ($a, $b) => $collator->compare($a->Person, $b->Person));
            $this->members[$unit->ID] = $unitMembers;
        }
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/group_members_form.latte');

        $this->resolveUsersRoles();

        $this->template->type       = $this->type;
        $this->template->patrolName = $this->patrolName;
        $this->template->units      = $this->units;
        $this->template->members    = $this->members;

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $roles             = $this->queryBus->handle(new RolesByTypeQuery($this->type));
        $roleSelectOptions = $this->getRoleSelectOptions($roles);

        $this->resolveUsersRoles();

        $form = $this->baseFormFactory->create();

        foreach ($this->units as $unit) {
            foreach ($this->members[$unit->ID] as $member) {
                $register = false;
                $role     = null;

                if ($this->usersRoles !== null) {
                    foreach ($this->usersRoles as $usersRole) {
                        if ($usersRole->getUser()->getSkautISPersonId() === $member->ID_Person) {
                            $register = true;
                            $role     = $usersRole->getRole();
                            break;
                        }
                    }
                }

                $memberId         = $member->ID;
                $registerCheckbox = $form->addCheckbox('register_' . $memberId)
                    ->setDefaultValue($register);
                $registerCheckbox
                    ->addCondition(Form::EQUAL, true)
                    ->toggle('roleselect-' . $memberId);

                $roleSelect = $form->addSelect('role_' . $memberId, null, $roleSelectOptions)
                    ->setHtmlId('roleselect-' . $memberId)
                    ->setHtmlAttribute('class', 'form-control-sm ignore-bs-select');
                if ($role != null) {
                    $roleSelect->setDefaultValue($role->getId());
                }

                $birthdate = new DateTimeImmutable($member->Birthday);
                $age       = $this->countAgeAt($birthdate, $this->seminarStart);
                foreach ($roles as $r) {
                    if ($age < $r->getMinimumAge()) {
                        $roleSelect
                            ->addConditionOn($registerCheckbox, Form::FILLED)
                            ->addCondition(Form::EQUAL, $r->getId())
                            ->addRule(Form::NOT_EQUAL, sprintf($r->getMinimumAgeWarning() ?: 'Osobě je %2$d, ale musí být min. %1$d let.', $r->getMinimumAge(), $age), $r->getId());
                    } elseif ($age > $r->getMaximumAge()) {
                        $roleSelect
                            ->addConditionOn($registerCheckbox, Form::FILLED)
                            ->addCondition(Form::EQUAL, $r->getId())
                            ->addRule(Form::NOT_EQUAL, sprintf($r->getMaximumAgeWarning() ?: 'Osobě je %2$d, ale musí být max. %1$d let.', $r->getMaximumAge(), $age), $r->getId());
                    }
                }
            }
        }

        $form->addSubmit('submit', 'Pokračovat');

        $form->setAction($this->getPresenter()->link('this', ['step' => 'members', 'type' => $this->type, 'patrol_id' => $this->patrolId]));

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
        Debugger::barDump($values, 'values');

        $selectedPersons = [];

        foreach ($this->units as $unit) {
            foreach ($this->members[$unit->ID] as $member) {
                $memberId          = $member->ID;
                $registerInputName = 'register_' . $memberId;
                $roleInputName     = 'role_' . $memberId;
                if ($values->$registerInputName) {
                    $selectedPersons[] = ['roleId' => $values->$roleInputName, 'personId' => $member->ID_Person];
                }
            }
        }

        if ($this->troop->getState() !== TroopApplicationState::DRAFT) {
            if ($this->type === 'patrol') {
                $patrol     = $this->queryBus->handle(new PatrolByIdQuery($this->patrolId));
                $usersCount = $patrol->getUsersRoles()->count();
            } else {
                $usersCount = $this->troop->getUsersRoles()->count();
            }

            if ($usersCount !== count($selectedPersons)) {
                $this->onError();

                return;
            }
        }

        $this->commandBus->handle(new UpdateGroupMembers($this->type, $this->troop->getId(), $this->patrolId, $selectedPersons));

        if (empty($selectedPersons)) {
            $this->onRemoveAll();
        }

        $this->onSave();
    }

    /**
     * @param Role[] $roles
     *
     * @return string[]
     */
    private function getRoleSelectOptions($roles): array
    {
        $options = [];

        foreach ($roles as $role) {
            $options[$role->getId()] = $role->getName();
        }

        return $options;
    }

    private function countAgeAt(DateTimeImmutable $birthdate, DateTimeImmutable $seminarStart): int
    {
        return $seminarStart->diff($birthdate)->y;
    }

    private function resolveUsersRoles(): void
    {
        $user        = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));
        $troop       = $this->queryBus->handle(new TroopByLeaderQuery($user->getId()));
        $this->troop = $troop;

        if ($this->type === 'patrol') {
            if ($this->patrolId !== null) {
                $patrol = $this->queryBus->handle(new PatrolByIdQuery($this->patrolId));
            } else {
                $patrol = $this->queryBus->handle(new PatrolByTroopAndNotConfirmedQuery($troop->getId()));
            }

            if ($patrol != null) {
                $this->usersRoles = $patrol->getUsersRoles();
                $this->patrolId   = $patrol->getId();
                $this->patrolName = $patrol->getName();
            } else {
                $this->usersRoles = new ArrayCollection();
            }
        } elseif ($this->type === 'troop') {
            $this->usersRoles = $troop->getUsersRoles();
        }
    }
}
