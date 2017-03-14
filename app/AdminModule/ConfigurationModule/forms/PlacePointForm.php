<?php

namespace App\AdminModule\ConfigurationModule\Forms;


use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Place\PlacePoint;
use App\Model\Settings\Place\PlacePointRepository;
use App\Model\Settings\SettingsRepository;
use Kdyby\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;

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

        $form->addText('name', 'admin.configuration.place_point_name')
            ->addRule(Form::FILLED, 'admin.configuration.place_point_name_empty');
        $form->addText('gpsLat', 'admin.configuration.place_point_gps_lat');
        $form->addText('gpsLon', 'admin.configuration.place_point_gps_lon');

        $form->addSubmit('submit', 'admin.common.save');

        if ($this->placePoint) {
            $form->setDefaults([
                'name' => $this->placePoint->getName(),
                'gpsLat' => $this->placePoint->getGpsLat(),
                'gpsLon' => $this->placePoint->getGpsLon()
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        if (!$this->placePoint)
            $this->placePoint = new PlacePoint();

        $this->placePoint->setName($values['name']);
        $this->placePoint->setGpsLat($values['gpsLat']);
        $this->placePoint->setGpsLon($values['gpsLon']);

        $this->placePointRepository->save($this->placePoint);
    }
}
