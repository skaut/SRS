<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\Program;
use App\Model\Program\Queries\ProgramAttendeesCountQuery;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use App\Services\ExcelExportService;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro zobrazení harmonogramu místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoomScheduleGridControl extends Control
{
    /**
     * Aktulní místnost
     */
    private ?Room $room = null;

    private ITranslator $translator;

    private RoomRepository $roomRepository;

    private ProgramRepository $programRepository;

    private ExcelExportService $excelExportService;

    private QueryBus $queryBus;

    public function __construct(
        ITranslator $translator,
        RoomRepository $roomRepository,
        ProgramRepository $programRepository,
        ExcelExportService $excelExportService,
        QueryBus $queryBus
    ) {
        $this->translator         = $translator;
        $this->roomRepository     = $roomRepository;
        $this->programRepository  = $programRepository;
        $this->excelExportService = $excelExportService;
        $this->queryBus           = $queryBus;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/room_schedule_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentRoomScheduleGrid(string $name): void
    {
        $this->room = $this->roomRepository->findById((int) $this->getPresenter()->getParameter('id'));

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->programRepository->createQueryBuilder('p')
            ->addSelect('b')->join('p.block', 'b')
            ->where('p.room = :room')->setParameter('room', $this->room));
        $grid->setDefaultSort(['start' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnDateTime('start', 'admin.program.rooms_schedule_program_start')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnDateTime('end', 'admin.program.rooms_schedule_program_end')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnText('name', 'admin.program.rooms_schedule_program_name', 'block.name');

        $grid->addColumnText('occupancy', 'admin.program.rooms_schedule_occupancy')
            ->setRenderer(
                function (Program $program) {
                    $capacity       = $this->room->getCapacity();
                    $attendeesCount = $this->queryBus->handle(new ProgramAttendeesCountQuery($program));

                    return $capacity === null ? $attendeesCount : $attendeesCount . '/' . $capacity;
                }
            );

        $grid->addToolbarButton('exportRoomsSchedule!', 'admin.program.rooms_schedule_download_schedule');
    }

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportRoomsSchedule(): void
    {
        $this->room = $this->roomRepository->findById((int) $this->getPresenter()->getParameter('id'));

        $response = $this->excelExportService->exportRoomSchedule($this->room, 'harmonogram-mistnosti.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
