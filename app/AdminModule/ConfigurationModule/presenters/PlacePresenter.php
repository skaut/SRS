<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Forms\PlaceDescriptionForm;
use App\AdminModule\ConfigurationModule\Forms\PlacePointForm;
use App\AdminModule\ConfigurationModule\Forms\SeminarForm;
use App\AdminModule\ConfigurationModule\Forms\SeminarFormFactory;
use Nette\Application\UI\Form;

class PlacePresenter extends ConfigurationBasePresenter
{
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

    public function renderEdit($id)
    {
        $placePoint = $this->placePointFormFactory->findById($id);
        $this->template->placePoint = $placePoint;
    }

    protected function createComponentPlaceDescriptionForm($name)
    {
        $form = $this->placeDescriptionFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentPlacePointForm($name)
    {
        $form = $this->placePointFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}