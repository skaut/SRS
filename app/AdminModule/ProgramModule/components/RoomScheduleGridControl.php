<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Services\ExcelExportService;
use App\Utils\Helpers;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use PhpOffice\PhpSpreadsheet\Exception;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro zobrazení harmonogramu místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 *
 * @property-read Template $template
 */
class RoomScheduleGridControl extends Control
{
    /**
     * Aktulní místnost
     * @var Room
     */
    private $room;

    /** @var Translator */
    private $translator;

    /** @var RoomRepository */
    private $roomRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var ExcelExportService */
    private $excelExportService;


    public function __construct(
        Translator $translator,
        RoomRepository $roomRepository,
        ProgramRepository $programRepository,
        ExcelExportService $excelExportService
    ) {
        parent::__construct();

        $this->translator         = $translator;
        $this->roomRepository     = $roomRepository;
        $this->programRepository  = $programRepository;
        $this->excelExportService = $excelExportService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/room_schedule_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridException
     */
    public function createComponentRoomScheduleGrid(string $name) : void
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
                function (Program $row) {
                    $capacity = $this->room->getCapacity();
                    if ($capacity === null) {
                        return $row->getAttendeesCount();
                    }
                    return $row->getAttendeesCount() . '/' . $capacity;
                }
            );

        $grid->addToolbarButton('exportRoomsSchedule!', 'admin.program.rooms_schedule_download_schedule');
    }

    /**
     * @throws AbortException
     * @throws \Exception
     */
    public function handleExportRoomsSchedule() : void
    {
        $this->room = $this->roomRepository->findById((int) $this->getPresenter()->getParameter('id'));

        $response = $this->excelExportService->exportRoomSchedule($this->room, 'harmonogram-mistnosti.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
