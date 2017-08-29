<?php

namespace App\AdminModule\StructureModule\Presenters;

use App\AdminModule\StructureModule\Components\ISubeventsGridControlFactory;
use App\AdminModule\StructureModule\Forms\SubeventForm;
use Nette\Forms\Form;


/**
 * Presenter obsluhující správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsPresenter extends StructureBasePresenter
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


    protected function createComponentSubeventsGrid()
    {
        return $this->subeventsGridControlFactory->create();
    }

    protected function createComponentSubeventForm()
    {
        $form = $this->subeventFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if (!$form['cancel']->isSubmittedBy())
                $this->flashMessage('admin.structure.subevents_saved', 'success');

            $this->redirect('Subevent:default');
        };

        return $form;
    }
}
