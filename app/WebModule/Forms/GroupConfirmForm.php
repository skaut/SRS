<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\User\Commands\ConfirmPatrol;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Queries\PatrolByTroopAndNotConfirmedQuery;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\UserGroupRole;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Collator;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function usort;

/**
 * Komponenta s formulářem pro potvrzení registrace družiny.
 */
class GroupConfirmForm extends UI\Control
{
    /** @var UserGroupRole[] */
    private array $usersRoles;

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
        private CommandBus $commandBus,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/group_confirm_form.latte');

        $this->resolveUsersRoles();

        $this->template->type       = $this->type;
        $this->template->patrolId   = $this->patrolId;
        $this->template->usersRoles = $this->usersRoles;

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $this->resolveUsersRoles();

        $form = $this->baseFormFactory->create();

        $form->addSubmit('submit', 'Pokračovat');

        $form->setAction($this->getPresenter()->link('this', ['step' => 'confirm', 'type' => $this->type, 'patrol_id' => $this->patrolId]));

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
        if ($this->type == 'patrol') {
            $this->commandBus->handle(new ConfirmPatrol($this->patrolId));
        }

        $this->onSave();
    }

    private function resolveUsersRoles(): void
    {
        $user  = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));
        $troop = $this->queryBus->handle(new TroopByLeaderQuery($user->getId()));

        if ($this->type == 'patrol') {
            if ($this->patrolId !== null) {
                $this->usersRoles = $this->queryBus->handle(new PatrolByIdQuery($this->patrolId))->getUsersRoles()->toArray();
            } else {
                $patrol           = $this->queryBus->handle(new PatrolByTroopAndNotConfirmedQuery($troop->getId()));
                $this->patrolId   = $patrol->getId();
                $this->usersRoles = $patrol->getUsersRoles()->toArray();
            }
        } elseif ($this->type === 'troop') {
            $this->usersRoles = $troop->getUsersRoles()->toArray();
        }

        $collator = new Collator('cs_CZ');
        usort($this->usersRoles, static fn ($a, $b) => $collator->compare($a->getUser()->getDisplayName(), $b->getUser()->getDisplayName()));
    }
}
