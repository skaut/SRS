<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\ExcelExportService;
use App\Services\QueryBus;
use App\Services\SubeventService;
use App\Utils\Validators;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Nette\Utils\ArrayHash;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;

/**
 * Komponenta pro správu programových bloků.
 */
class ProgramBlocksGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly Translator $translator,
        private readonly BlockRepository $blockRepository,
        private readonly UserRepository $userRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly Validators $validators,
        private readonly SubeventService $subeventService,
        private readonly Session $session,
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/program_blocks_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentProgramBlocksGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->blockRepository->createQueryBuilder('b')
            ->addSelect('c')->leftJoin('b.category', 'c')
            ->addSelect('s')->leftJoin('b.subevent', 's'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);
        $grid->setColumnsHideable();
        $grid->setStrictSessionFilterValues(false);

        $grid->addColumnText('name', 'admin.program.blocks.common.name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('subevent', 'admin.program.blocks.common.subevent', 'subevent.name')
            ->setSortable('s.name')
            ->setFilterMultiSelect($this->subeventService->getSubeventsOptions(), 's.id');

        $grid->addColumnText('category', 'admin.program.blocks.common.category', 'category.name')
            ->setSortable('c.name')
            ->setFilterMultiSelect($this->categoryRepository->getCategoriesOptions(), 'c.id');

        $grid->addColumnText('lectors', 'admin.program.blocks.common.lectors', 'lectorsText')
            ->setFilterMultiSelect($this->userRepository->getLectorsOptions())
            ->setCondition(static function (QueryBuilder $qb, ArrayHash $values): void {
                $qb->join('b.lectors', 'l')
                    ->andWhere('l.id IN (:lids)')
                    ->setParameter('lids', (array) $values);
            });

        $grid->addColumnNumber('duration', 'admin.program.blocks.common.duration')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('capacity', 'admin.program.blocks.common.capacity')
            ->setRendererOnCondition(fn ($row) => $this->translator->translate('admin.program.blocks.common.capacity_unlimited'), static fn (Block $row) => $row->getCapacity() === null)
            ->setSortable();

        $grid->addColumnText('alternatesAllowed', 'admin.program.blocks.column.alternates_allowed')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ])
            ->setSortable()
            ->setFilterSelect([null => 'admin.common.all', 1 => 'admin.common.yes', 0 => 'admin.common.no'])
            ->setTranslateOptions();

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.program.blocks.common.mandatory');
        $columnMandatory
            ->addOption(ProgramMandatoryType::VOLUNTARY, 'common.program_mandatory_type.voluntary')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(ProgramMandatoryType::MANDATORY, 'common.program_mandatory_type.mandatory')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(ProgramMandatoryType::AUTO_REGISTERED, 'common.program_mandatory_type.auto_registered')
            ->setClass('btn-warning')
            ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $columnMandatory
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                ProgramMandatoryType::VOLUNTARY => 'common.program_mandatory_type.voluntary',
                ProgramMandatoryType::MANDATORY => 'common.program_mandatory_type.mandatory',
                ProgramMandatoryType::AUTO_REGISTERED => 'common.program_mandatory_type.auto_registered',
            ])
            ->setTranslateOptions();

        $grid->addColumnNumber('programsCount', 'admin.program.blocks.column.programs_count')
            ->setRenderer(static fn (Block $row) => $row->getProgramsCount());

        if (
            ($this->getPresenter()->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS) ||
                $this->getPresenter()->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS)) &&
            $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_ADD_BLOCK))
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
                'data-content' => $this->translator->translate('admin.program.blocks.action.delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', [$this, 'isAllowedModifyBlock']);

        $grid->addGroupAction('admin.program.blocks.action.export_blocks_attendees')
            ->onSelect[] = [$this, 'groupExportBlocksAttendees'];

        return $grid;
    }

    /**
     * Odstraní programový blok.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDelete(int $id): void
    {
        $block = $this->blockRepository->findById($id);

        $p = $this->getPresenter();

        if (! $this->userRepository->findById($this->getPresenter()->getUser()->getId())->isAllowedModifyBlock($block)) {
            $p->flashMessage('admin.program.blocks.message.delete_not_allowed', 'danger');
            $p->redirect('this');
        }

        $this->commandBus->handle(new RemoveBlock($block));

        $p->flashMessage('admin.program.blocks.message.delete_success', 'success');
        $p->redirect('this');
    }

    /**
     * Změní povinnost bloku.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function changeMandatory(string $id, string $mandatory): void
    {
        $block = $this->blockRepository->findById((int) $id);

        $p = $this->getPresenter();

        if (! $this->isAllowedModifyBlock($block)) {
            $p->flashMessage('admin.program.blocks.message.mandatory_change_denied', 'danger');
        } elseif ($mandatory  === ProgramMandatoryType::AUTO_REGISTERED && ! $this->validators->validateBlockAutoRegistered($block, $block->getCapacity())) {
            $p->flashMessage('admin.program.blocks.message.mandatory_change_auto_registered_not_allowed', 'danger');
        } else {
            try {
                $blockOld = clone $block;
                $block->setMandatory($mandatory);
                $this->commandBus->handle(new SaveBlock($block, $blockOld));
                $p->flashMessage('admin.program.blocks.message.mandatory_change_success', 'success');
            } catch (Throwable $ex) {
                Debugger::log($ex, ILogger::WARNING);
                $p->flashMessage('admin.program.blocks.message.mandatory_change_failed', 'danger');
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('programBlocksGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Hromadně vyexportuje seznam uživatelů, kteří mají blok zapsaný.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportBlocksAttendees(array $ids): void
    {
        $this->sessionSection->blockIds = $ids;
        $this->redirect('exportblocksattendees');
    }

    /**
     * Zpracuje export seznamu uživatelů, kteří mají blok zapsaný.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportBlocksAttendees(): void
    {
        $ids = $this->session->getSection('srs')->blockIds;

        $blocks = $this->blockRepository->findBlocksByIds($ids);

        $response = $this->excelExportService->exportBlocksAttendees($blocks, 'ucastnici-bloku.xlsx');

        $this->getPresenter()->sendResponse($response);
    }

    /**
     * Vrací true, pokud je uživatel oprávněn upravovat programový blok.
     */
    public function isAllowedModifyBlock(Block $block): bool
    {
        $presenter = $this->getPresenter();
        assert($presenter instanceof AdminBasePresenter);

        return $presenter->dbuser->isAllowedModifyBlock($block);
    }
}
