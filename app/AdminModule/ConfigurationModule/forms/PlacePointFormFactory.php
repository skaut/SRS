<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Settings\Place\PlacePoint;
use App\Model\Settings\Place\PlacePointRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use VojtechDobes\NetteForms\GpsPicker;

/**
 * Formulář pro úpravu mapového bodu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePointFormFactory
{
    use Nette\SmartObject;

    /** @var ?PlacePoint */
    private $placePoint;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var PlacePointRepository */
    private $placePointRepository;

    public function __construct(BaseFormFactory $baseForm, PlacePointRepository $placePointRepository)
    {
        $this->baseFormFactory      = $baseForm;
        $this->placePointRepository = $placePointRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : BaseForm
    {
        $this->placePoint = $this->placePointRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addText('name', 'admin.configuration.place_points_name')
            ->addRule(Form::FILLED, 'admin.configuration.place_points_name_empty');

        $gpsPicker = $form->addGpsPicker('gps', 'admin.configuration.place_points_place');
        $gpsPicker->setDriver(GpsPicker::DRIVER_SEZNAM);
        $gpsPicker->setSize('100%', 400);

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');

        if ($this->placePoint) {
            $form->setDefaults([
                'name' => $this->placePoint->getName(),
                'gps' => [
                    'lat' => $this->placePoint->getGpsLat(),
                    'lng' => $this->placePoint->getGpsLon(),
                ],
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        if (! $this->placePoint) {
            $this->placePoint = new PlacePoint();
        }

        $this->placePoint->setName($values->name);
        $this->placePoint->setGpsLat($values->gps->lat);
        $this->placePoint->setGpsLon($values->gps->lng);

        $this->placePointRepository->save($this->placePoint);
    }
}
