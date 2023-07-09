<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\AdminModule\ProgramModule\Components\IRoomScheduleGridControlFactory;
use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;
use App\AdminModule\ProgramModule\Components\RoomScheduleGridControl;
use App\AdminModule\ProgramModule\Components\RoomsGridControl;
use App\Model\Acl\Permission;
use App\Model\Program\Repositories\RoomRepository;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu místností.
 */
class RoomsPresenter extends ProgramBasePresenter
{
    #[Inject]
    public RoomRepository $roomRepository;

    #[Inject]
    public IRoomsGridControlFactory $roomsGridControlFactory;

    #[Inject]
    public IRoomScheduleGridControlFactory $roomScheduleGridControlFactory;

    /** @throws AbortException */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE_ROOMS);
    }

    public function renderDetail(int $id): void
    {
        $room = $this->roomRepository->findById($id);

        $this->template->room = $room;
    }

    protected function createComponentRoomsGrid(): RoomsGridControl
    {
        return $this->roomsGridControlFactory->create();
    }

    protected function createComponentRoomScheduleGrid(): RoomScheduleGridControl
    {
        return $this->roomScheduleGridControlFactory->create();
    }
}
