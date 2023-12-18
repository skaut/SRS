<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Group\Commands\RemoveStatus;
use App\Model\Group\Commands\SaveStatus;
use App\Model\Group\Repositories\StatusRepository;
use App\Model\Group\Status;
use App\Services\AclService;
use App\Services\CommandBus;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Localization\Translator;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;

/**
 * Komponenta pro správu kategorií.
 */
class StatusGridControl extends Control
{
    public function __construct(private CommandBus $commandBus, private Translator $translator, private StatusRepository $statusRepository, private RoleRepository $roleRepository, private AclService $aclService)
    {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/status_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentStatusGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->statusRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.groups.status.column.name');

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.groups.status.column.name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.groups.status.column.name_exists', $this->statusRepository->findAllNames());
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.groups.status.column.name_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Status $item): void {
            $nameText = $container['name'];
            assert($nameText instanceof TextInput);
            $nameText->addRule(Form::IS_NOT_IN, 'admin.groups.status.column.name_exists', $this->statusRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.groups.status.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání kategorie.
     */
    public function add(stdClass $values): void
    {
        $status = new Status($values->name);

        $this->commandBus->handle(new SaveStatus($status));

        $this->getPresenter()->flashMessage('admin.groups.status.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu kategorie.
     *
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values): void
    {
        $status    = $this->statusRepository->findById((int) $id);
        $statusOld = clone $status;

        $status->setName($values->name);

        $this->commandBus->handle(new SaveStatus($status));

        $this->getPresenter()->flashMessage('admin.groups.status.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Odstraní kategorii.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDelete(int $id): void
    {
        $status = $this->statusRepository->findById($id);

        $p = $this->getPresenter();

        if ($status->getBlocks()->isEmpty()) {
            $this->commandBus->handle(new RemoveStatus($status));
            $p->flashMessage('admin.groups.status.message.delete_success', 'success');
        } else {
            $p->flashMessage('admin.groups.status.message.delete_failed', 'danger');
        }

        $p->redirect('this');
    }
}
