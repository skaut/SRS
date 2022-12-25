<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

use App\Model\Acl\Role;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Troop;
use App\Services\ExcelExportService;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
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
class TroopsGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private Translator $translator,
        private TroopRepository $repository,
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
        $this->template->setFile(__DIR__ . '/templates/troops_grid.latte');
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
        $grid->setDataSource($this->repository->createQueryBuilder('p'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $stamp = date(Helpers::DATE_FORMAT);
        $grid->addExportCsv('admin.common.export_all', 'NSJ2023 Skupiny ' . $stamp . '.csv');
        $grid->addExportCsvFiltered('admin.common.export_filter', 'NSJ2023 Skupiny fi ' . $stamp . '.csv');

        $grid->addGroupAction('Export seznamu skupin')
            ->onSelect[] = [$this, 'groupExportUsers'];

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setRenderer(fn ($t) => $this->translator->translate('common.application_state.' . $t->getState()))
            ->setFilterText();

        $grid->addColumnText('variableSymbol', 'Variabilní symbol ')
            ->setSortable()
            ->setRenderer(static fn ($t) => $t->getVariableSymbolText())
            ->setFilterText();

        $grid->addColumnText('leader', 'Vedoucí')->setSortable()
            ->setRenderer(function (Troop $t) {
                $leader = $t->getLeader();

                return Html::el('a')->setAttribute('href', $this->getPresenter()->link('Users:detail', $leader->getId()))->setText($leader->getDisplayName());
            })
            ->setFilterText();

        $grid->addColumnDateTime('applicationDate', 'Datum založení')
            ->setRenderer(static function (Troop $p) {
                $date = $p->getApplicationDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();

        $grid->addColumnNumber('fee', 'Cena')->setSortable()->setFilterText();

        $grid->addColumnDateTime('maturityDate', 'Datum splatnosti')
            ->setFormat(Helpers::DATE_FORMAT)
            ->setSortable();

        $grid->addColumnDateTime('paymentDate', 'Datum platby')
            ->setFormat(Helpers::DATE_FORMAT)
            ->setSortable();

        $grid->addColumnText('pairingCode', 'Kód jamoddílu')->setFilterText();

        $grid->addColumnNumber('numPersons', '# osob')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::PATROL_LEADER, Role::LEADER, Role::ESCORT, Role::ATTENDEE]));

        $grid->addColumnNumber('numChilder', '# rádců')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::PATROL_LEADER]));

        $grid->addColumnNumber('numAdults', '# dospělých')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => $p->countUsersInRoles([Role::LEADER, Role::ESCORT]));

        $grid->addColumnNumber('numPatrols', '# družin')
//      ->setSortableCallback(static fn($qb,$vals) =>sort($vals))
            ->setRenderer(static fn (Troop $p) => count($p->getConfirmedPatrols()));

        $grid->addAction('detail', 'admin.common.detail', 'Troops:detail')
            ->setClass('btn btn-xs btn-primary');

//        $grid->addAction('delete', '', 'delete!')
//            ->setIcon('trash')
//            ->setTitle('admin.common.delete')
//            ->setClass('btn btn-xs btn-danger')
//            ->addAttributes([
//                'data-toggle' => 'confirmation',
//                'data-content' => $this->translator->translate('admin.users.users_delete_confirm'),
//            ]);

        return $grid;
    }

//    /**
//     * Zpracuje odstranění externí skupiny.
//     *
//     * @throws AbortException
//     */
//    public function handleDelete(int $id): void
//    {
//        $rec = $this->repository->findById($id);
//
//        $this->repository->remove($rec);
//
//        $p = $this->getPresenter();
//        $p->flashMessage('Skupina smazána.', 'success');
//        $p->redirect('this');
//    }

    /**
     * Hromadně vyexportuje seznam družin.
     *
     * @param int[] $ids
     *
     * @throws AbortException
     */
    public function groupExportUsers(array $ids): void
    {
        $this->sessionSection->patrolIds = $ids;
        $this->redirect('exportusers');
    }

    /**
     * Zpracuje export seznamu družin.
     *
     * @throws AbortException
     * @throws Exception
     */
    public function handleExportTroops(): void
    {
        $ids = $this->session->getSection('srs')->patrolIds;

        $res = $this->repository->createQueryBuilder('p')->where('p.id IN (:ids)') // stejne se v teto class querybuilder pouziva
        ->setParameter('ids', $ids)->getQuery()->getResult(); // otestovat , podivat se na vzor (export uzivatelu)

        $users    = new ArrayCollection($res);
        $response = $this->excelExportService->exportUsersList($users, 'seznam-uzivatelu.xlsx'); // nutna nova metoda

        $this->getPresenter()->sendResponse($response);
    }
}
