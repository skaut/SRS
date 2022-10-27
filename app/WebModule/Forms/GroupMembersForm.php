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
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use Collator;
use DateTimeImmutable;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function count;
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

        $this->template->units   = $this->units;
        $this->template->members = $this->members;

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $user = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));

        $roles             = $this->queryBus->handle(new RolesByTypeQuery($this->type));
        $roleSelectOptions = $this->getRoleSelectOptions($roles);

        $troop       = $this->queryBus->handle(new TroopByLeaderQuery($user->getId()));
        $this->troop = $troop;
        $usersRoles  = null;
        if ($this->type === 'patrol') {
            if ($this->patrolId !== null) {
                $patrol = $this->queryBus->handle(new PatrolByIdQuery($this->patrolId));
            } else {
                $patrol = $this->queryBus->handle(new PatrolByTroopAndNotConfirmedQuery($troop->getId()));
            }

            if ($patrol != null) {
                $usersRoles     = $patrol->getUsersRoles();
                $this->patrolId = $patrol->getId();
            }
        } elseif ($this->type === 'troop') {
            $usersRoles = $troop->getUsersRoles();
        }

        $form = $this->baseFormFactory->create();

        foreach ($this->units as $unit) {
            foreach ($this->members[$unit->ID] as $member) {
                $register = false;
                $role     = null;

                if ($usersRoles !== null) {
                    foreach ($usersRoles as $usersRole) {
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
                            ->addRule(Form::NOT_EQUAL, $r->getMinimumAgeWarning() ? $r->getMinimumAgeWarning() : 'Příliš nízký věk.', $r->getId());
                    } elseif ($age > $r->getMaximumAge()) {
                        $roleSelect
                            ->addConditionOn($registerCheckbox, Form::FILLED)
                            ->addCondition(Form::EQUAL, $r->getId())
                            ->addRule(Form::NOT_EQUAL, $r->getMinimumAgeWarning() ? $r->getMinimumAgeWarning() : 'Příliš vysoký věk.', $r->getId());
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
}
