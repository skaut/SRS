<?php

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\Place\PlacePointRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


class PlacePointsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var PlacePointRepository */
    private $placePointRepository;

    public function __construct(Translator $translator, PlacePointRepository $placePointRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->placePointRepository = $placePointRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/place_points_grid.latte');
    }

    public function createComponentPlacePointsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->placePointRepository->createQueryBuilder('p')->orderBy('p.name'));
        $grid->setPagination(false);


        $grid->addColumnText('name', 'admin.configuration.place_points_name');
        $grid->addColumnText('gps', 'admin.configuration.place_points_gps')
            ->setRenderer(function ($row) {
                $lat = $row->getGpsLat();
                $lon = $row->getGpsLon();

                $latText = number_format(abs($lat), 7) . ($lat >= 0 ? 'N' : 'S');
                $lonText = number_format(abs($lon), 7) . ($lon >= 0 ? 'E' : 'W');

                return $latText . ", " . $lonText;
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
                'data-content' => $this->translator->translate('admin.configuration.place_points_delete_confirm')
            ]);
    }

    public function handleDelete($id)
    {
        $input = $this->placePointRepository->findById($id);
        $this->placePointRepository->remove($input);

        $this->getPresenter()->flashMessage('admin.configuration.place_points_deleted', 'success');

        $this->redirect('this');
    }
}