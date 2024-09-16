<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Components;

use App\Model\Group\Commands\RemoveGroup;
use App\Model\Group\Commands\SaveGroup;
use App\Model\Group\Group;
use App\Model\Group\Repositories\GroupRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Localization\Translator;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;

/**
 * Komponenta pro správu místností.
 */
class GroupsGridControl extends Control
{
    public function __construct(
        private CommandBus $commandBus,
        private Translator $translator,
        private GroupRepository $groupRepository,
        private readonly UserRepository $userRepository,
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/groups_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentGroupsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->groupRepository->createQueryBuilder('g'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.groups.group.column.name');

        $grid->addColumnText('group_status', 'admin.groups.group.column.group_status');

        $grid->addColumnText('leader_id', 'admin.groups.group.column.leader_name')
            ->setRenderer(function (Group $row) {
                    $user = $this->userRepository->findById($row->getLeaderId());

                    return $user->getFirstName() . ' ' . $user->getLastName();
            })
/*
            ->setRenderer(static fn (Group $row) => Html::el('span')
                ->setText(
                        $user = $this->userRepository->findById((int) $id);
                        $this->userService->setApproved($user, (bool) $approved);
                        $row->getEmail();
                        )
                   )
*/
            ->setFilterText();

        $grid->addColumnText('leader_email', 'admin.groups.group.column.leader_email');
        $grid->addColumnText('places', 'admin.groups.group.column.places');

        $grid->addColumnText('price', 'admin.groups.group.column.price');

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.groups.column.name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.groups.column.name_exists', $this->groupRepository->findAll());

            $container->addText('places', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.groups.column.capacity_format');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.groups.column.name_empty');

            $container->addText('places', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.groups.column.capacity_format');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Group $item): void {
            $nameText = $container['name'];
            assert($nameText instanceof TextInput);
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.groups.column.name_exists', $this->groupRepository->findAll());

            $container->setDefaults([
                'name' => $item->getName(),
                'places' => $item->getPlaces(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('detail', 'admin.common.detail', 'Groups:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.groups.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání místnosti.
     */
    public function add(stdClass $values): void
    {
        $group = new Group($values->name, $values->capacity !== '' ? $values->capacity : null);

        $this->commandBus->handle(new SaveGroup($group));

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.groups.message.save_success', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu místnosti.
     */
    public function edit(string $id, stdClass $values): void
    {
        $group = $this->groupRepository->findById((int) $id);

        $group->setName($values->name);
        $group->setCapacity($values->capacity !== '' ? $values->capacity : null);

        $this->commandBus->handle(new SaveGroup($group));

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.groups.message.save_success', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Odstraní místnost.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $group = $this->groupRepository->findById($id);

        $this->commandBus->handle(new RemoveGroup($group));

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.groups.message.delete_success', 'success');
        $p->redirect('this');
    }
}
