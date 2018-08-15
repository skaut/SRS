<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\IPlacePointsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\PlaceDescriptionForm;
use App\AdminModule\ConfigurationModule\Forms\PlacePointForm;
use App\Model\Settings\Place\PlacePointRepository;
use App\Model\Settings\SettingsException;
use Nette\Application\UI\Form;

/**
 * Presenter obsluhující nastavení místa semináře.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlacePresenter extends ConfigurationBasePresenter
{
    /**
     * @var PlacePointRepository
     * @inject
     */
    public $placePointRepository;

    /**
     * @var PlaceDescriptionForm
     * @inject
     */
    public $placeDescriptionFormFactory;

    /**
     * @var PlacePointForm
     * @inject
     */
    public $placePointFormFactory;

    /**
     * @var IPlacePointsGridControlFactory
     * @inject
     */
    public $placePointsGridControlFactory;


    public function renderEdit($id) : void
    {
        $placePoint                 = $this->placePointRepository->findById($id);
        $this->template->placePoint = $placePoint;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    protected function createComponentPlaceDescriptionForm() : Form
    {
        $form = $this->placeDescriptionFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentPlacePointForm()
    {
        $form = $this->placePointFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, array $values) : void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Place:default');
            }

            $this->flashMessage('admin.configuration.place_points_saved', 'success');

            $this->redirect('Place:default');
        };

        return $form;
    }

    protected function createComponentPlacePointsGrid()
    {
        return $this->placePointsGridControlFactory->create();
    }
}
