<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\IPlacePointsGridControlFactory;
use App\AdminModule\ConfigurationModule\Components\PlacePointsGridControl;
use App\AdminModule\ConfigurationModule\Forms\PlaceDescriptionFormFactory;
use App\AdminModule\ConfigurationModule\Forms\PlacePointFormFactory;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\PlacePointRepository;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení místa semináře.
 */
class PlacePresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public PlacePointRepository $placePointRepository;

    /** @inject */
    public PlaceDescriptionFormFactory $placeDescriptionFormFactory;

    /** @inject */
    public PlacePointFormFactory $placePointFormFactory;

    /** @inject */
    public IPlacePointsGridControlFactory $placePointsGridControlFactory;

    public function renderEdit(int $id): void
    {
        $placePoint                 = $this->placePointRepository->findById($id);
        $this->template->placePoint = $placePoint;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentPlaceDescriptionForm(): Form
    {
        $form = $this->placeDescriptionFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentPlacePointForm(): Form
    {
        $form = $this->placePointFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('Place:default');
            }

            $this->flashMessage('admin.configuration.place_points_saved', 'success');

            $this->redirect('Place:default');
        };

        return $form;
    }

    protected function createComponentPlacePointsGrid(): PlacePointsGridControl
    {
        return $this->placePointsGridControlFactory->create();
    }
}
