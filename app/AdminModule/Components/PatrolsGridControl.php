<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\User\Patrol;
use App\Model\User\Repositories\PatrolRepository;
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
class PatrolsGridControl extends Control
{
    private SessionSection $sessionSection;

    public function __construct(
        private Translator $translator,
        private PatrolRepository $repository,
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
        $grid->setDataSource($this->repository->createQueryBuilder('p')->where('p.confirmed = true'));
        $grid->setDefaultSort(['displayName' => 'ASC']);
        $grid->setColumnsHideable();
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $stamp = date('Y-m-d H.m.s');
        $grid->addExportCsv('admin.common.export_all', 'NSJ2023 Druziny ' . $stamp . 'csv');
        $grid->addExportCsvFiltered('admin.common.export_filter', 'NSJ2023 Druziny fi ' . $stamp . 'csv');

        $grid->addGroupAction('Export seznamu družin')
            ->onSelect[] = [$this, 'groupExportUsers'];

        $grid->addColumnText('id', 'ID')
            ->setSortable();

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();

//        $grid->addColumnText('leader', 'Vedoucí')
//            ->setRenderer(static function (Patrol $p) {
//                return $leader = $p->countUsersInRoles([Role::LEADER]); {{ todo
//
//            })
//            ->setFilterText();

        $grid->addColumnDateTime('created', 'Datum založení')
            ->setRenderer(static function (Patrol $p) {
                $date = $p->getTroop()->getApplicationDate();

                return $date ? $date->format(Helpers::DATETIME_FORMAT) : '';
            })
            ->setSortable();

        $grid->addColumnText('troop', 'Oddíl - přidat link')
            ->setRenderer(function (Patrol $p) {
                $troop = $p->getTroop();

                return Html::el('a')->setAttribute('href', $this->link('troopDetail', $troop->getId()))->setText($troop->getName());
            }); // link na oddíl

        $grid->addColumnText('userRoles', 'Počet 1')
            ->setRenderer(static fn (Patrol $p) => count($p->getUsersRoles())); // je to správné číslo?

//        $grid->addColumnText('notRegisteredMandatoryBlocksCount', 'admin.users.users_not_registered_mandatory_blocks')
//            ->setRenderer(static function (User $user) {
//                return Html::el('span')
//                    ->setAttribute('data-toggle', 'tooltip')
//                    ->setAttribute('title', $user->getNotRegisteredMandatoryBlocksText())
//                    ->setText($user->getNotRegisteredMandatoryBlocksCount());
//            })
//            ->setSortable();


        $grid->addAction('detail', 'admin.common.detail', 'Users:detail') // destinace
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
//     * Zpracuje odstranění externího uživatele.
//     *
//     * @throws AbortException
//     */
//    public function handleDelete(int $id): void
//    {
//        $patrol = $this->repository->findById($id);
//
//        $this->repository->remove($patrol);
//
//        $p = $this->getPresenter();
//        $p->flashMessage('Družina smazána', 'success');
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
    public function handleExportUsers(): void
    {
        $ids = $this->session->getSection('srs')->patrolIds;

        $res = $this->repository->createQueryBuilder('p')->where('p.id IN (:ids)') // stejne se v teto class querybuilder pouziva
        ->setParameter('ids', $ids)->getQuery()->getResult(); // otestovat , podivat se na vzor (export uzivatelu)

        $users    = new ArrayCollection($res);
        $response = $this->excelExportService->exportUsersList($users, 'seznam-uzivatelu.xlsx'); // nutna nova metoda

        $this->getPresenter()->sendResponse($response);
    }
}
