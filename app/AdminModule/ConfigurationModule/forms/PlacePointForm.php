<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Settings\Place\PlacePoint;
use App\Model\Settings\Place\PlacePointRepository;
use Nette;
use Nette\Application\UI\Form;
use VojtechDobes\NetteForms\GpsPicker;


/**
 * Formulář pro úpravu mapového bodu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePointForm
{
    use Nette\SmartObject;

    /** @var PlacePoint */
    private $placePoint;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var PlacePointRepository */
    private $placePointRepository;


    /**
     * PlacePointForm constructor.
     * @param BaseForm $baseForm
     * @param PlacePointRepository $placePointRepository
     */
    public function __construct(BaseForm $baseForm, PlacePointRepository $placePointRepository)
    {
        $this->baseFormFactory = $baseForm;
        $this->placePointRepository = $placePointRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->placePoint = $this->placePointRepository->findById($id);

        $form = $this->baseFormFactory->create();

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

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param array $values
     */
    public function processForm(Form $form, array $values)
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
