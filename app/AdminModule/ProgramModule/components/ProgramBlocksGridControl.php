<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\Program\BlockRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class ProgramBlocksGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var BlockRepository
     */
    private $blockRepository;

    public function __construct(Translator $translator, BlockRepository $blockRepository)
    {
        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_blocks_grid.latte');
    }

    public function createComponentProgramBlocksGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')); //TODO default order name

        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.blocks_name');

        $grid->addColumnText('category', 'admin.program.blocks_category');

        $grid->addColumnText('lector', 'admin.program.blocks_lector');

        $grid->addColumnText('room', 'admin.program.blocks_room');

        $grid->addColumnText('duration', 'admin.program.blocks_duration');

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity');

        $grid->addColumnText('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getPrograms()->count();
            });

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
        $grid->getInlineEdit()->setShowNonEditingColumns();
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('admin.program.rooms_delete_confirm', 'name');
    }

    public function add($values) {

    }

    public function edit($id, $values)
    {

    }

    public function handleDelete($id)
    {
        $this->blockRepository->removeBlock($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.blocks_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['programBlocksGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }
}