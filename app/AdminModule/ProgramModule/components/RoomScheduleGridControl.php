<?php

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
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


    /**
     * RoomScheduleGridControl constructor.
     * @param Translator $translator
     * @param RoomRepository $roomRepository
     * @param ProgramRepository $programRepository
     */
    public function __construct(Translator $translator, RoomRepository $roomRepository,
                                ProgramRepository $programRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->roomRepository = $roomRepository;
        $this->programRepository = $programRepository;
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

        $grid->addColumnText('name', 'admin.program.rooms_schedule_program_name', 'block.name');

        $grid->addColumnDateTime('start', 'admin.program.rooms_schedule_program_start')
            ->setFormat('j. n. Y H:i');;

        $grid->addColumnDateTime('end', 'admin.program.rooms_schedule_program_end')
            ->setFormat('j. n. Y H:i');;

        $grid->addColumnText('occupancy', 'admin.program.rooms_schedule_occupancy')
            ->setRenderer(
                function ($row) {
                    $capacity = $this->room->getCapacity();
                    if ($capacity === NULL)
                        return $row->getAttendeesCount();
                    return $row->getAttendeesCount() . "/" . $capacity;
                }
            );


        $grid->addToolbarButton('exportRoomSchedule!', 'admin.program.rooms_schedule_download_schedule');
    }

    public function handleExportRoomSchedule() {

    }
}
