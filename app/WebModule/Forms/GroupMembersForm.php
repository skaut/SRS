<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Acl\Queries\RolesByTypeQuery;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\User;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use Collator;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

use function usort;

/**
 * Komponenta s formulářem pro výběr členů družiny.
 */
class GroupMembersForm extends UI\Control
{
    private static array $ALLOWED_UNIT_TYPES = ['oddil'];

    /**
     * Přihlášený uživatel.
     */
    private ?User $user = null;

    private array $units;

    private array $members;

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
        private SkautIsService $skautIsService
    ) {
        $this->units   = $this->skautIsService->getUnitAllUnit(self::$ALLOWED_UNIT_TYPES);
        $this->members = [];

        $collator = new Collator('cs_CZ');
        foreach ($this->units as $unit) {
            $unitMembers = $this->skautIsService->getMembershipAll($unit->ID);
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
        $this->user        = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));
        $roleSelectOptions = $this->getRoleSelectOptions();

        $form = $this->baseFormFactory->create();

        foreach ($this->units as $unit) {
            foreach ($this->members[$unit->ID] as $member) {
                $memberId = $member->ID;
                $form->addCheckbox('register_' . $memberId)
                    ->addCondition(Form::EQUAL, true)
                    ->toggle('roleselect-' . $memberId);
                $form->addSelect('role_' . $memberId, null, $roleSelectOptions)
                    ->setHtmlId('roleselect-' . $memberId)
                    ->setHtmlAttribute('class', 'form-control-sm ignore-bs-select');
            }
        }

        $form->addSubmit('submit', 'Pokračovat');

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
        $this->onSave();
    }

    private function getRoleSelectOptions(): array
    {
        $roles = $this->queryBus->handle(new RolesByTypeQuery($this->type));

        $options = [];

        foreach ($roles as $role) {
            $options[$role->getId()] = $role->getName();
        }

        return $options;
    }
}
