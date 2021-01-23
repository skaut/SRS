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
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\ExcelExportService;
use App\Services\ISettingsService;
use App\Services\SubeventService;
use App\Utils\Validators;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu programových bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramBlocksGridControl extends Control
{
    private CommandBus $commandBus;

    private ITranslator $translator;

    private BlockRepository $blockRepository;

    private ISettingsService $settingsService;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private ExcelExportService $excelExportService;

    private Validators $validators;

    private Session $session;

    private SessionSection $sessionSection;

    private SubeventService $subeventService;

    public function __construct(
        CommandBus $commandBus,
        ITranslator $translator,
        BlockRepository $blockRepository,
        ISettingsService $settingsService,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        ExcelExportService $excelExportService,
        Validators $validators,
        SubeventService $subeventService,
        Session $session
    ) {
        $this->commandBus         = $commandBus;
        $this->translator         = $translator;
        $this->blockRepository    = $blockRepository;
        $this->settingsService    = $settingsService;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->excelExportService = $excelExportService;
        $this->validators         = $validators;
        $this->subeventService    = $subeventService;

        $this->session        = $session;
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
     * @throws SettingsException
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentProgramBlocksGrid(string $name): void
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

        $grid->addGroupAction('admin.program.blocks_group_action_export_blocks_attendees')
            ->onSelect[] = [$this, 'groupExportBlocksAttendees'];

        $grid->addColumnText('name', 'admin.program.blocks_name')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('subevent', 'admin.program.blocks_subevent', 'subevent.name')
            ->setSortable('s.name')
            ->setFilterMultiSelect($this->subeventService->getSubeventsOptions(), 's.id');

        $grid->addColumnText('category', 'admin.program.blocks_category', 'category.name')
            ->setSortable('c.name')
            ->setFilterMultiSelect($this->categoryRepository->getCategoriesOptions(), 'c.id');

        $grid->addColumnText('lectors', 'admin.program.blocks_lectors', 'lectorsText')
            ->setFilterMultiSelect($this->userRepository->getLectorsOptions())
            ->setCondition(static function (QueryBuilder $qb, ArrayHash $values): void {
                $qb->join('b.lectors', 'l')
                    ->andWhere('l.id IN (:lids)')
                    ->setParameter('lids', (array) $values);
            });

        $grid->addColumnNumber('duration', 'admin.program.blocks_duration')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('capacity', 'admin.program.blocks_capacity')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.program.blocks_capacity_unlimited');
            }, static function (Block $row) {
                return $row->getCapacity() === null;
            })
            ->setSortable();

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.program.blocks_mandatory');
        $columnMandatory
            ->addOption(ProgramMandatoryType::VOLUNTARY, 'admin.program.blocks_mandatory_voluntary')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(ProgramMandatoryType::MANDATORY, 'admin.program.blocks_mandatory_mandatory')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(ProgramMandatoryType::AUTO_REGISTERED, 'admin.program.blocks_mandatory_auto_registered')
            ->setClass('btn-warning')
            ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $columnMandatory
            ->setSortable()
            ->setFilterSelect([
                '' => 'admin.common.all',
                ProgramMandatoryType::VOLUNTARY => 'admin.program.blocks_mandatory_voluntary',
                ProgramMandatoryType::MANDATORY => 'admin.program.blocks_mandatory_mandatory',
                ProgramMandatoryType::AUTO_REGISTERED => 'admin.program.blocks_mandatory_auto_registered',
            ])
            ->setTranslateOptions();

        $grid->addColumnNumber('programsCount', 'admin.program.blocks_programs_count')
            ->setRenderer(static function (Block $row) {
                return $row->getProgramsCount();
            });

        if (
            ($this->getPresenter()->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_ALL_PROGRAMS) ||
                $this->getPresenter()->user->isAllowed(SrsResource::PROGRAM, Permission::MANAGE_OWN_PROGRAMS)) &&
            $this->settingsService->getBoolValue(Settings::IS_ALLOWED_ADD_BLOCK)
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
                'data-content' => $this->translator->translate('admin.program.blocks_delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', [$this, 'isAllowedModifyBlock']);
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

        if (! $this->userRepository->findById($this->getPresenter()->getUser()->getId())->isAllowedModifyBlock($block)) {
            $this->getPresenter()->flashMessage('admin.program.blocks_delete_not_allowed', 'danger');
            $this->redirect('this');
        }

        $this->commandBus->handle(new RemoveBlock($block));

        $this->getPresenter()->flashMessage('admin.program.blocks_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní povinnost bloku.
     *
     * @throws AbortException
     */
    public function changeMandatory(string $id, string $mandatory): void
    {
        $block = $this->blockRepository->findById((int) $id);

        $p = $this->getPresenter();

        if (! $this->isAllowedModifyBlock($block)) {
            $p->flashMessage('admin.program.blocks_change_mandatory_denied', 'danger');
        } elseif ($mandatory  === ProgramMandatoryType::AUTO_REGISTERED && ! $this->validators->validateBlockAutoRegistered($block)) {
            $p->flashMessage('admin.program.blocks_change_mandatory_auto_registered_not_allowed', 'danger');
        } else {
            try {
                $blockOld = clone $block;
                $block->setMandatory($mandatory);
                $this->commandBus->handle(new SaveBlock($block, $blockOld));
                $p->flashMessage('admin.program.blocks_changed_mandatory', 'success');
            } catch (Throwable $ex) {
                Debugger::log($ex, ILogger::WARNING);
                $p->flashMessage('admin.program.blocks_change_mandatory_error', 'danger');
            }
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $programBlocksGrid */
            $programBlocksGrid = $this['programBlocksGrid'];
            $programBlocksGrid->redrawItem($id);
        } else {
            $this->redirect('this');
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
        $this->redirect('exportblocksattendees'); // presmerovani kvuli zruseni ajax
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
        /** @var AdminBasePresenter $presenter */
        $presenter = $this->getPresenter();

        return $presenter->dbuser->isAllowedModifyBlock($block);
    }
}
