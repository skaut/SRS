<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

use App\Model\User\Patrol;
use App\Model\User\Repositories\PatrolRepository;
use App\Utils\Helpers;
use Nette\Application\UI\Control;
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
    public function __construct(
        private Translator $translator,
        private PatrolRepository $repository
    ) {
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

        $stamp = date(Helpers::DATE_FORMAT);
        $grid->addExportCsv('admin.common.export_all', 'NSJ2023 Druziny ' . $stamp . '.csv');
        $grid->addExportCsvFiltered('admin.common.export_filter', 'NSJ2023 Druziny fi ' . $stamp . '.csv');

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
            })
            ->setSortable();

        $grid->addColumnText('userRoles', 'Počet osob')
            ->setRenderer(static fn (Patrol $p) => count($p->getUsersRoles())); // je to správné číslo?

//        $grid->addAction('detail', 'admin.common.detail', 'Patrols:detail') // destinace
//            ->setClass('btn btn-xs btn-primary');

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
}
