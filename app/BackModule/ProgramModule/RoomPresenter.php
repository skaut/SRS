<?php

namespace BackModule\ProgramModule;

/**
 * Obsluhuje sekci mistnosti
 */
class RoomPresenter extends \BackModule\BasePresenter
{
    protected $resource = \SRS\Model\Acl\Resource::ROOM;

    /**
     * @var \SRS\Model\Program\RoomRepository
     */
    protected $roomRepo;

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(\SRS\Model\Acl\Permission::MANAGE);
        $this->roomRepo = $this->context->database->getRepository('\SRS\Model\Program\Room');
    }

    public function beforeRender()
    {
        parent::beforeRender();
    }

    public function renderList()
    {
        $rooms = $this->roomRepo->findAll();
        $this->template->rooms = $rooms;
    }

    public function handleDelete($id)
    {
        $room = $this->roomRepo->find($id);
        if ($room == null) throw new \Nette\Application\BadRequestException('Místnost s tímto ID neexistuje', 404);
        $this->context->database->getRepository('\SRS\Model\Program\Block')->updateRooms($id, 'NULL');
        $this->context->database->remove($room);
        $this->context->database->flush();
        $this->flashMessage('Místnost smazána', 'success');
        $this->redirect(":Back:Program:Room:list");
    }


    protected function createComponentRoomForm()
    {
        return new \SRS\Form\Program\RoomForm(null, null, $this->presenter->dbsettings, $this->context->database, $this->user);
    }

    protected function createComponentRoomGrid()
    {
        return new \SRS\Components\RoomGrid($this->context->database);
    }

}
