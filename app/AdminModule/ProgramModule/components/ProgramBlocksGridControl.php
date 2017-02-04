<?php

namespace App\AdminModule\ProgramModule\Components;


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
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var BlockRepository
     */
    private $blockRepository;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(Translator $translator, BlockRepository $blockRepository, SettingsRepository $settingsRepository, UserRepository $userRepository, CategoryRepository $categoryRepository)
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
            ->setFilterMultiSelect($this->prepareCategoriesChoices(), 'c.id');

        $grid->addColumnText('lector', 'admin.program.blocks_lector', 'lector.name')
            ->setSortable('l.displayName')
            ->setFilterMultiSelect($this->prepareLectorsChoices(), 'l.id');

        $basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');
        $translator = $this->translator;

        $grid->addColumnText('duration', 'admin.program.blocks_duration')
            ->setRenderer(function ($row) use ($translator, $basicBlockDuration) {
                return $translator->translate('admin.common.minutes', null, ['count' => $row->getDurationInMinutes($basicBlockDuration)]);
            })
            ->setSortable()
            ->setFilterMultiSelect($this->prepareDurationsChoices($translator));

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity')
            ->setSortable();

        $mandatoryColumn = $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory_grid');
        $mandatoryColumn
            ->addOption(false, 'admin.program.blocks_mandatory_grid_voluntary')->setClass('btn-success')->endOption()
            ->addOption(true, 'admin.program.blocks_mandatory_grid_mandatory')->setClass('btn-danger')->endOption()
            ->onChange[] = [$this, 'mandatoryChange'];
        $mandatoryColumn
            ->setSortable()
            ->setFilterSelect([
                '' => $translator->translate('admin.common.all'),
                false => $translator->translate('admin.program.blocks_mandatory_grid_voluntary'),
                true => $translator->translate('admin.program.blocks_mandatory_grid_mandatory')
            ]);

        $grid->addColumnText('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getProgramsCount();
            });

        $grid->addToolbarButton('Blocks:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('detail', '', 'Blocks:detail')
            ->setIcon('eye')
            ->setTitle('admin.common.detail')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', '', 'Blocks:edit')
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
        }
        else {
            $this->redirect('this');
        }
    }

    public function mandatoryChange($id, $mandatory)
    {
        $this->blockRepository->editBlockMandatory($id, $mandatory);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.blocks_changed_mandatory', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['programBlocksGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    private function prepareDurationsChoices($translator) {
        $basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');
        $choices = [];
        for ($i = 1; $basicBlockDuration * $i <= 240; $i++) {
            $choices[$i] = $translator->translate('admin.common.minutes', null, ['count' => $basicBlockDuration * $i]);
        }
        return $choices;
    }

    private function prepareLectorsChoices() {
        $choices = [];
        foreach ($this->userRepository->findApprovedUsersInRole(Role::LECTOR) as $user)
            $choices[$user->getId()] = $user->getDisplayName();
        return $choices;
    }

    private function prepareCategoriesChoices() {
        $choices = [];
        foreach ($this->categoryRepository->findAll() as $category)
            $choices[$category->getId()] = $category->getName();
        return $choices;
    }
}