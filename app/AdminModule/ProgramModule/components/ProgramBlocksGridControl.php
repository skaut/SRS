<?php

namespace App\AdminModule\ProgramModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu programových bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    /** @var ProgramRepository */
    private $programRepository;


    /**
     * ProgramBlocksGridControl constructor.
     * @param Translator $translator
     * @param BlockRepository $blockRepository
     * @param SettingsRepository $settingsRepository
     * @param UserRepository $userRepository
     * @param CategoryRepository $categoryRepository
     * @param ProgramRepository $programRepository
     */
    public function __construct(Translator $translator, BlockRepository $blockRepository,
                                SettingsRepository $settingsRepository, UserRepository $userRepository,
                                CategoryRepository $categoryRepository, ProgramRepository $programRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->blockRepository = $blockRepository;
        $this->settingsRepository = $settingsRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->programRepository = $programRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_blocks_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentProgramBlocksGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')
            ->addSelect('l')->leftJoin('b.lector', 'l')
            ->addSelect('c')->leftJoin('b.category', 'c')
        );
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.program.blocks_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('category', 'admin.program.blocks_category', 'category.name')
            ->setSortable('c.name')
            ->setFilterMultiSelect($this->categoryRepository->getCategoriesOptions(), 'c.id');

        $grid->addColumnText('lector', 'admin.program.blocks_lector', 'lector.displayName')
            ->setSortable('l.displayName')
            ->setFilterMultiSelect($this->userRepository->getLectorsOptions(), 'l.id');

        $grid->addColumnNumber('duration', 'admin.program.blocks_duration')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.program.blocks_capacity_unlimited');
            }, function ($row) {
                return $row->getCapacity() === NULL;
            }
            )
            ->setSortable();

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory');
        $columnMandatory
            ->addOption(0, 'admin.program.blocks_mandatory_voluntary')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(1, 'admin.program.blocks_mandatory_mandatory')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(2, 'admin.program.blocks_mandatory_auto_register')
            ->setClass('btn-warning')
            ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $columnMandatory
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                0 => 'admin.program.blocks_mandatory_voluntary',
                1 => 'admin.program.blocks_mandatory_mandatory',
                2 => 'admin.program.blocks_mandatory_auto_register'
            ])
            ->setTranslateOptions();

        $grid->addColumnNumber('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(function ($row) {
                return $row->getProgramsCount();
            });

        if (($this->getPresenter()->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS) ||
                $this->getPresenter()->user->isAllowed(Resource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS)) &&
            $this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_BLOCK)
        ) {
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

    /**
     * Odstraní programový blok.
     * @param $id
     */
    public function handleDelete($id)
    {
        $block = $this->blockRepository->findById($id);

        if (!$this->userRepository->findById($this->getPresenter()->getUser()->getId())->isAllowedModifyBlock($block)) {
            $this->getPresenter()->flashMessage('admin.program.blocks_delete_not_allowed', 'danger');
            $this->redirect('this');
        }

        $this->blockRepository->remove($block);

        $this->getPresenter()->flashMessage('admin.program.blocks_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní povinnost bloku.
     * @param $id
     * @param $mandatory
     */
    public function changeMandatory($id, $mandatory)
    {
        $block = $this->blockRepository->findById($id);

        $p = $this->getPresenter();

        if (!$p->dbuser->isAllowedModifyBlock($block)) {
            $p->flashMessage('admin.program.blocks_change_mandatory_denied', 'danger');
        } elseif ($mandatory == 2 && $block->getMandatory() != 2 &&
            ($block->getProgramsCount() > 1 ||
                ($block->getProgramsCount() == 1 && $this->programRepository->hasOverlappingProgram(
                        $block->getPrograms()->first(),
                        $block->getPrograms()->first()->getStart(),
                        $block->getPrograms()->first()->getEnd())
                )
            )
        ) {
            $p->flashMessage('admin.program.blocks_change_mandatory_auto_register_not_allowed', 'danger');
        } else {
            //odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
            if ($block->getMandatory() == 2 && $mandatory != 2) {
                foreach ($block->getPrograms() as $program) {
                    $program->removeAllAttendees();
                }
            }
            //pridani ucastniku, pokud je pridana automaticke prihlaseni
            if ($mandatory == 2 && $block->getMandatory() != 2) {
                foreach ($block->getPrograms() as $program) {
                    $program->setAttendees($this->userRepository->findProgramAllowed($program));
                }
            }

            $block->setMandatory($mandatory);
            $this->blockRepository->save($block);

            $p->flashMessage('admin.program.blocks_changed_mandatory', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['programBlocksGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Vrací true, pokud je uživatel oprávněn upravovat programový blok.
     * @param $block
     * @return mixed
     */
    public function isAllowedModifyBlock($block)
    {
        return $this->getPresenter()->dbuser->isAllowedModifyBlock($block);
    }
}
