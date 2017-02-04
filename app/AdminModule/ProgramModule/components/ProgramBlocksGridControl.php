<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\Program\BlockRepository;
use App\Model\Program\Category;
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
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')->join(Category::class, 'c')); //TODO default order name

        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.blocks_name'); //TODO sort, filters

        $grid->addColumnText('c.name', 'admin.program.blocks_category');

        $grid->addColumnText('lector', 'admin.program.blocks_lector');

        $grid->addColumnText('duration', 'admin.program.blocks_duration');

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity');

        $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory_grid');

        $grid->addColumnText('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getPrograms()->count();
            });

        $grid->addToolbarButton('Blocks:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', '', 'Block:edit')
            ->setIcon('pencil-square-o')
            ->setTitle('admin.common.edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm')
            ]);
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