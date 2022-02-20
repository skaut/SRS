<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\Commands\RemoveRoom;
use App\Model\Program\Commands\SaveRoom;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use App\Services\CommandBus;
use App\Services\ExcelExportService;
use Doctrine\ORM\ORMException;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;

/**
 * Komponenta pro správu místností.
 */
class RoomsGridControl extends Control
{
    private CommandBus $commandBus;

    private Translator $translator;

    private RoomRepository $roomRepository;

    private ExcelExportService $excelExportService;

    private Session $session;

    private SessionSection $sessionSection;

    public function __construct(
        CommandBus $commandBus,
        Translator $translator,
        RoomRepository $roomRepository,
        ExcelExportService $excelExportService,
        Session $session
    ) {
        $this->commandBus         = $commandBus;
        $this->translator         = $translator;
        $this->roomRepository     = $roomRepository;
        $this->excelExportService = $excelExportService;

        $this->session        = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/rooms_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentRoomsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roomRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addGroupAction('admin.program.rooms.action.export_rooms_schedules')
            ->onSelect[] = [$this, 'groupExportRoomsSchedules'];

        $grid->addColumnText('name', 'admin.program.rooms.column.name');

        $grid->addColumnText('capacity', 'admin.program.rooms.column.capacity')
            ->setRendererOnCondition(function (Room $row) {
                return $this->translator->translate('admin.program.blocks.column.capacity_unlimited');
            }, static function (Room $row) {
                return $row->getCapacity() === null;
            });

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.rooms.column.name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.rooms.column.name_exists', $this->roomRepository->findAllNames());

            $container->addText('capacity', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.rooms.column.capacity_format');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.rooms.column.name_empty');

            $container->addText('capacity', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.rooms.column.capacity_format');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Room $item): void {
            $nameText = $container['name'];
            assert($nameText instanceof TextInput);
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.rooms.column.name_exists', $this->roomRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'capacity' => $item->getCapacity(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('detail', 'admin.common.detail', 'Rooms:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.rooms.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání místnosti.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function add(stdClass $values): void
    {
        $room = new Room($values->name, $values->capacity !== '' ? $values->capacity : null);

        $this->commandBus->handle(new SaveRoom($room));

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.rooms.message.save_success', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu místnosti.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function edit(string $id, stdClass $values): void
    {
        $room = $this->roomRepository->findById((int) $id);

        $room->setName($values->name);
        $room->setCapacity($values->capacity !== '' ? $values->capacity : null);

        $this->commandBus->handle(new SaveRoom($room));

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.rooms.message.save_success', 'success');
        $p->redrawControl('flashes');
    }

    /**
     * Odstraní místnost.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $room = $this->roomRepository->findById($id);

        $this->commandBus->handle(new RemoveRoom($room));

        $this->getPresenter()->flashMessage('admin.program.rooms.message.delete_success', 'success');
        $this->redirect('this');
    }

    /**
     * Hromadně vyexportuje harmonogramy místností.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportRoomsSchedules(array $ids): void
    {
        $this->sessionSection->roomIds = $ids;
        $this->redirect('exportroomsschedules'); // presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export harmonogramů místností.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportRoomsSchedules(): void
    {
        $ids = $this->session->getSection('srs')->roomIds;

        $blocks = $this->roomRepository->findRoomsByIds($ids);

        $response = $this->excelExportService->exportRoomsSchedules($blocks, 'harmonogramy-mistnosti.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
