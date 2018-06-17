<?php
declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IRoomScheduleGridControlFactory;
use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\Model\ACL\Permission;
use App\Model\Program\RoomRepository;


/**
 * Presenter obsluhující správu místností.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RoomsPresenter extends ProgramBasePresenter
{
    /**
     * @var RoomRepository
     * @inject
     */
    public $roomRepository;

    /**
     * @var IRoomsGridControlFactory
     * @inject
     */
    public $roomsGridControlFactory;

    /**
     * @var IRoomScheduleGridControlFactory
     * @inject
     */
    public $roomScheduleGridControlFactory;


    /**
     * @throws \Nette\Application\AbortException
     */
    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE_ROOMS);
    }

    /**
     * @param $id
     */
    public function renderDetail($id)
    {
        $room = $this->roomRepository->findById($id);

        $this->template->room = $room;
    }

    protected function createComponentRoomsGrid()
    {
        return $this->roomsGridControlFactory->create();
    }

    protected function createComponentRoomScheduleGrid()
    {
        return $this->roomScheduleGridControlFactory->create();
    }
}
