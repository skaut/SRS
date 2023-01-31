<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

use App\Model\User\Commands\RemovePatrol;
use App\Model\User\Patrol;
use App\Model\User\Repositories\PatrolRepository;
use App\Services\CommandBus;
use App\Services\ExcelExportService;
use App\Utils\Helpers;
use Exception;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function count;
use function date;

/**
 * Komponenta pro zobrazení datagridu družin.
 */
class PatrolsGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private CommandBus $commandBus,
        private Translator $translator,
        private PatrolRepository $patrolRepository,
        private ExcelExportService $excelExportService,
        private Session $session
    ) {
        $this->sessionSection = $session->getSection('srs');
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/patrols_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws Throwable
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentPatrolsGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->patrolRepository->createQueryBuilder('p')->where('p.confirmed = true'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addGroupAction('Export seznamu družin')
            ->onSelect[] = [$this, 'groupExportPatrols'];

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('troop', 'Skupina')
            ->setRenderer(function (Patrol $p) {
                $troop = $p->getTroop();

                return Html::el('a')->setAttribute('href', $this->getPresenter()->link('Troops:detail', $troop->getId()))->setText($troop->getName());
            });

        $grid->addColumnDateTime('created', 'Datum založení')
            ->setRenderer(static function (Patrol $p) {
                $date = $p->getTroop()->getApplicationDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            });

        $grid->addColumnNumber('userRoles', 'Počet osob')
            ->setRenderer(static fn (Patrol $p) => count($p->getUsersRoles())); // je to správné číslo?

//        $grid->addAction('detail', 'admin.common.detail', 'Patrols:detail') // destinace
//            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('Opravdu chcete družinu odstranit?'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění družiny.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $patrol = $this->patrolRepository->findById($id);
        $this->commandBus->handle(new RemovePatrol($patrol));
        $p = $this->getPresenter();
        $p->flashMessage('Družina byla úspěšně odstraněna.', 'success');
        $p->redirect('this');
    }

    /**
     * Hromadně vyexportuje seznam družin.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportPatrols(array $ids): void
    {
        $this->sessionSection->patrolIds = $ids;
        $this->redirect('exportpatrols');
    }

    /**
     * Zpracuje export seznamu družin.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportPatrols(): void
    {
        $ids = $this->session->getSection('srs')->patrolIds;

        $patrols = $this->patrolRepository->findPatrolsByIds($ids);

        $response = $this->excelExportService->exportPatrolsList($patrols, 'seznam-druzin.xlsx');

        $this->getPresenter()->sendResponse($response);
    }
}
