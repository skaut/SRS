<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Program\BlockRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class ProgramBlocksGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    public function __construct(Translator $translator, BlockRepository $blockRepository,
                                SettingsRepository $settingsRepository, UserRepository $userRepository,
                                CategoryRepository $categoryRepository)
    {
        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_blocks_grid.latte');
    }

    public function createComponentProgramBlocksGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')
            ->addSelect('l')->leftJoin('b.lector', 'l')
            ->addSelect('c')->leftJoin('b.category', 'c')
        );
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);


        $grid->addColumnText('name', 'admin.program.blocks_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('category', 'admin.program.blocks_category', 'category.name')
            ->setSortable('c.name')
            ->setFilterMultiSelect($this->categoryRepository->getCategoriesOptions(), 'c.id');

        $grid->addColumnText('lector', 'admin.program.blocks_lector', 'lector.displayName')
            ->setSortable('l.displayName')
            ->setFilterMultiSelect($this->userRepository->getLectorsOptions(), 'l.id');

        $grid->addColumnText('duration', 'admin.program.blocks_duration')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity')
            ->setRendererOnCondition(function ($row) {
                    return $this->translator->translate('admin.program.blocks_capacity_unlimited');
                }, function ($row) {
                    return $row->getCapacity() === null;
                }
            )
            ->setSortable();

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory');
        $columnMandatory
            ->addOption(false, 'admin.program.blocks_mandatory_voluntary')
                ->setClass('btn-success')
                ->endOption()
            ->addOption(true, 'admin.program.blocks_mandatory_mandatory')
                ->setClass('btn-danger')
                ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $columnMandatory
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                false => 'admin.program.blocks_mandatory_voluntary',
                true => 'admin.program.blocks_mandatory_mandatory'
            ])
            ->setTranslateOptions();

        $grid->addColumnText('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getProgramsCount();
            });

        if (($this->getPresenter()->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS) ||
            $this->getPresenter()->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS)) &&
            $this->settingsRepository->getValue('is_allowed_add_block')) {
            $grid->addToolbarButton('Blocks:add')
                ->setIcon('plus')
                ->setTitle('admin.common.add');
        }

        $grid->addAction('detail', 'admin.common.detail', 'Blocks:detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', 'admin.common.edit', 'Blocks:edit');
        $grid->allowRowsAction('edit', [$this, 'isAllowedModifyBlock']);

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', [$this, 'isAllowedModifyBlock']);
    }

    public function handleDelete($id)
    {
        $block = $this->blockRepository->findById($id);
        $this->blockRepository->remove($block);

        $this->getPresenter()->flashMessage('admin.program.blocks_deleted', 'success');

        $this->redirect('this');
    }

    public function changeMandatory($id, $mandatory) {
        $block = $this->blockRepository->findById($id);

        $p = $this->getPresenter();

        if (!$p->dbuser->isAllowedModifyBlock($block)) {
            $p->flashMessage('admin.program.blocks_change_mandatory_denied', 'danger');
        }
        else {
            $block->setMandatory($mandatory);
            $this->blockRepository->save($block);

            $p->flashMessage('admin.program.blocks_changed_mandatory', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['programBlocksGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    public function isAllowedModifyBlock($block) {
        return $this->getPresenter()->dbuser->isAllowedModifyBlock($block);
    }
}