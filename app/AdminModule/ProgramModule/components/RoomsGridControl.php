<?php

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Program\Room;
use App\Model\Program\RoomRepository;
use App\Services\ExcelExportService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu místností.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoomsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var RoomRepository */
    private $roomRepository;

    /** @var ExcelExportService */
    private $excelExportService;

    /** @var Session */
    private $session;

    /** @var SessionSection */
    private $sessionSection;


    /**
     * RoomsGridControl constructor.
     * @param Translator $translator
     * @param RoomRepository $roomRepository
     * @param ExcelExportService $excelExportService
     * @param Session $session
     */
    public function __construct(Translator $translator, RoomRepository $roomRepository,
                                ExcelExportService $excelExportService, Session $session)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->roomRepository = $roomRepository;
        $this->excelExportService = $excelExportService;

        $this->session = $session;
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/rooms_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentRoomsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roomRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addGroupAction('admin.program.rooms_group_action_export_rooms_schedules')
            ->onSelect[] = [$this, 'groupExportRoomsSchedules'];


        $grid->addColumnText('name', 'admin.program.rooms_name');

        $grid->addColumnText('capacity', 'admin.program.rooms_capacity')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.program.blocks_capacity_unlimited');
            }, function ($row) {
                return $row->getCapacity() === NULL;
            });

        $grid->addInlineAdd()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.rooms_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.rooms_name_exists', $this->roomRepository->findAllNames());

            $container->addText('capacity', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.rooms_capacity_format');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.rooms_name_empty');

            $container->addText('capacity', '')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER, 'admin.program.rooms_capacity_format');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.program.rooms_name_exists', $this->roomRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'capacity' => $item->getCapacity()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('detail', 'admin.common.detail', 'Rooms:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.rooms_delete_confirm')
            ]);
    }

    /**
     * Zpracuje přidání místnosti.
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $room = new Room();

        $room->setName($values['name']);
        $room->setCapacity($values['capacity'] !== '' ? $values['capacity'] : NULL);

        $this->roomRepository->save($room);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.rooms_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu místnosti.
     * @param $id
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function edit($id, $values)
    {
        $room = $this->roomRepository->findById($id);

        $room->setName($values['name']);
        $room->setCapacity($values['capacity'] !== '' ? $values['capacity'] : NULL);

        $this->roomRepository->save($room);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.rooms_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Odstraní místnost.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $room = $this->roomRepository->findById($id);
        $this->roomRepository->remove($room);

        $this->getPresenter()->flashMessage('admin.program.rooms_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Hromadně vyexportuje harmonogramy místností.
     * @param array $ids
     * @throws \Nette\Application\AbortException
     */
    public function groupExportRoomsSchedules(array $ids)
    {
        $this->sessionSection->roomIds = $ids;
        $this->redirect('exportroomsschedules'); //presmerovani kvuli zruseni ajax
    }

    /**
     * Zpracuje export harmonogramů místností.
     * @throws \PHPExcel_Exception
     * @throws \Nette\Application\AbortException
     */
    public function handleExportRoomsSchedules()
    {
        $ids = $this->session->getSection('srs')->roomIds;

        $blocks = $this->roomRepository->findRoomsByIds($ids);

        $response = $this->excelExportService->exportRoomsSchedules($blocks, "harmonogramy-mistnosti.xlsx");

        $this->getPresenter()->sendResponse($response);
    }
}
