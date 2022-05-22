<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\PlacePoint;
use App\Model\Settings\Repositories\PlacePointRepository;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function abs;
use function number_format;

/**
 * Komponenta pro správu mapových bodů.
 */
class PlacePointsGridControl extends Control
{
    public function __construct(private Translator $translator, private PlacePointRepository $placePointRepository)
    {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/place_points_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentPlacePointsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->placePointRepository->createQueryBuilder('p')->orderBy('p.name'));
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.place_points_name');
        $grid->addColumnText('gps', 'admin.configuration.place_points_gps')
            ->setRenderer(static function (PlacePoint $row) {
                $lat = $row->getGpsLat();
                $lon = $row->getGpsLon();

                $latText = number_format(abs($lat), 7) . ($lat >= 0 ? 'N' : 'S');
                $lonText = number_format(abs($lon), 7) . ($lon >= 0 ? 'E' : 'W');

                return $latText . ', ' . $lonText;
            });

        $grid->addToolbarButton('Place:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Place:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.place_points_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje odstranění mapového bodu.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $input = $this->placePointRepository->findById($id);
        $this->placePointRepository->remove($input);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.place_points_deleted', 'success');
        $p->redirect('this');
    }
}
