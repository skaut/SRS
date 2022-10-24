<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\User\Queries\PatrolByTroopAndNotConfirmedQuery;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\Queries\UsersRolesByPatrolQuery;
use App\Model\User\Queries\UsersRolesByTroopQuery;
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

        $form->addSubmit('submit', 'Pokračovat');

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
                $this->usersRoles = $this->queryBus->handle(new UsersRolesByPatrolQuery($this->patrolId))->toArray();
            } else {
                $patrol           = $this->queryBus->handle(new PatrolByTroopAndNotConfirmedQuery($troop->getId()));
                $this->patrolId   = $patrol->getId();
                $this->usersRoles = $this->queryBus->handle(new UsersRolesByPatrolQuery($patrol->getId()))->toArray();
            }
        } elseif ($this->type === 'troop') {
            $this->usersRoles = $this->queryBus->handle(new UsersRolesByTroopQuery($troop->getId()))->toArray();
        }

        $collator = new Collator('cs_CZ');
        usort($this->usersRoles, static fn ($a, $b) => $collator->compare($a->getUser()->getDisplayName(), $b->getUser()->getDisplayName()));
    }
}
