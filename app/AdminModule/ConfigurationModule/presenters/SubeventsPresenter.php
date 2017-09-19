<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\ISubeventsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\SubeventForm;
use App\Model\Structure\SubeventRepository;
use Nette\Forms\Form;


/**
 * Presenter obsluhující správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ISubeventsGridControlFactory
     * @inject
     */
    public $subeventsGridControlFactory;

    /**
     * @var SubeventForm
     * @inject
     */
    public $subeventFormFactory;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;


    /**
     * @param $id
     */
    public function renderEdit($id)
    {
        $subevent = $this->subeventRepository->findById($id);

        $this->template->editedSubevent = $subevent;
    }

    protected function createComponentSubeventsGrid()
    {
        return $this->subeventsGridControlFactory->create();
    }

    protected function createComponentSubeventForm()
    {
        $form = $this->subeventFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if (!$form['cancel']->isSubmittedBy())
                $this->flashMessage('admin.configuration.subevents_saved', 'success');

            $this->redirect('Subevents:default');
        };

        return $form;
    }
}
