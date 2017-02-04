<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\Program\RoomRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class RoomsGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var RoomRepository
     */
    private $roomRepository;

    public function __construct(Translator $translator, RoomRepository $roomRepository)
    {
        $this->translator = $translator;
        $this->roomRepository = $roomRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/rooms_grid.latte');
    }

    public function createComponentRoomsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roomRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.rooms_name');

        $grid->addInlineAdd()->onControlAdd[] = function($container) {
            $container->addText('name', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addText('name', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'name' => $item->getName()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.rooms_delete_confirm')
            ]);
    }

    public function add($values) {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.program.rooms_name_empty', 'danger');
        }
        elseif (!$this->roomRepository->isNameUnique($name)) {
            $p->flashMessage('admin.program.rooms_name_not_unique', 'danger');
        }
        else {
            $this->roomRepository->addRoom($name);
            $p->flashMessage('admin.program.rooms_added', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['roomsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.program.rooms_name_empty', 'danger');
        }
        elseif (!$this->roomRepository->isNameUnique($name, $id)) {
            $p->flashMessage('admin.program.rooms_name_not_unique', 'danger');
        }
        else {
            $this->roomRepository->editRoom($id, $name);
            $p->flashMessage('admin.program.rooms_edited', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
        } else {
            $this->redirect('this');
        }
    }

    public function handleDelete($id)
    {
        $this->roomRepository->removeRoom($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.rooms_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['roomsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }
}