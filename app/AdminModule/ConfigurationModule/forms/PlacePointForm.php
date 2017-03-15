<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Place\PlacePoint;
use App\Model\Settings\Place\PlacePointRepository;
use Nette;
use Nette\Application\UI\Form;
use VojtechDobes\NetteForms\GpsPicker;


class PlacePointForm extends Nette\Object
{
    /** @var PlacePoint */
    private $placePoint;

    /** @var BaseForm */
    private $baseForm;

    /** @var PlacePointRepository */
    private $placePointRepository;


    public function __construct(BaseForm $baseForm, PlacePointRepository $placePointRepository)
    {
        $this->baseForm = $baseForm;
        $this->placePointRepository = $placePointRepository;
    }

    public function create($id)
    {
        $this->placePoint = $this->placePointRepository->findById($id);

        $form = $this->baseForm->create();

        $form->addText('name', 'admin.configuration.place_points_name')
            ->addRule(Form::FILLED, 'admin.configuration.place_points_name_empty');

        $form->addGpsPicker('gps', 'admin.configuration.place_points_place')
            ->setDriver(GpsPicker::DRIVER_SEZNAM)
            ->setSize("100%", 400);

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->placePoint) {
            $form->setDefaults([
                'name' => $this->placePoint->getName(),
                'gps' => [
                    'lat' => $this->placePoint->getGpsLat(),
                    'lng' => $this->placePoint->getGpsLon()
                ]
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->placePoint)
                $this->placePoint = new PlacePoint();

            $this->placePoint->setName($values['name']);
            $this->placePoint->setGpsLat($values['gps']->lat);
            $this->placePoint->setGpsLon($values['gps']->lng);

            $this->placePointRepository->save($this->placePoint);
        }
    }
}
