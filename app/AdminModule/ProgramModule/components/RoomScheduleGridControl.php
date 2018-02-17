<?php

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Services\ExcelExportService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro zobrazení harmonogramu místnosti.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
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


    /**
     * RoomScheduleGridControl constructor.
     * @param Translator $translator
     * @param RoomRepository $roomRepository
     * @param ProgramRepository $programRepository
     * @param ExcelExportService $excelExportService
     */
    public function __construct(Translator $translator, RoomRepository $roomRepository,
                                ProgramRepository $programRepository, ExcelExportService $excelExportService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->roomRepository = $roomRepository;
        $this->programRepository = $programRepository;
        $this->excelExportService = $excelExportService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/room_schedule_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentRoomScheduleGrid($name)
    {
        $this->room = $this->roomRepository->findById($this->getPresenter()->getParameter('id'));

        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->programRepository->createQueryBuilder('p')
            ->addSelect('b')->join('p.block', 'b')
            ->where('p.room = :room')->setParameter('room', $this->room)
        );
        $grid->setDefaultSort(['start' => 'ASC']);
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('start', 'admin.program.rooms_schedule_program_start')
            ->setFormat('j. n. Y H:i');;

        $grid->addColumnDateTime('end', 'admin.program.rooms_schedule_program_end')
            ->setFormat('j. n. Y H:i');;

        $grid->addColumnText('name', 'admin.program.rooms_schedule_program_name', 'block.name');

        $grid->addColumnText('occupancy', 'admin.program.rooms_schedule_occupancy')
            ->setRenderer(
                function ($row) {
                    $capacity = $this->room->getCapacity();
                    if ($capacity === NULL)
                        return $row->getAttendeesCount();
                    return $row->getAttendeesCount() . "/" . $capacity;
                }
            );


        $grid->addToolbarButton('exportRoomsSchedule!', 'admin.program.rooms_schedule_download_schedule');
    }

    /**
     * @throws \Nette\Application\AbortException
     * @throws \PHPExcel_Exception
     */
    public function handleExportRoomsSchedule()
    {
        $this->room = $this->roomRepository->findById($this->getPresenter()->getParameter('id'));

        $response = $this->excelExportService->exportRoomSchedule($this->room, 'harmonogram-mistnosti.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
