<?php

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomText;
use App\Model\Settings\Place\PlacePointRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;

class PlacePointsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var PlacePointRepository */
    private $placePointRepository;

    public function __construct(Translator $translator, PlacePointRepository $placePointRepository)
    {
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
        $grid->addColumnNumber('gpsLat', 'admin.configuration.place_points_latitude')
            ->setFormat(5);
        $grid->addColumnNumber('gpsLon', 'admin.configuration.place_points_longitude')
            ->setFormat(5);


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