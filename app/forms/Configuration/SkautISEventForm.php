<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Configuration;

use Nette\Application\UI,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro synchronizaci se skautIS akci
 */
class SkautISEventForm extends UI\Form
{

    protected $dbsettings;

    protected $skautISEvents;

    public function __construct(IContainer $parent = NULL, $name = NULL, $skautISEvents)
    {
        parent::__construct($parent, $name);

        $this->skautISEvents = $skautISEvents;

        $this->addSelect('skautISEvent', 'Vyberte akci ze skautIS')->setItems(\SRS\Form\EntityForm::getFormChoices($this->skautISEvents, 'ID', 'DisplayName'))
            ->addRule(Form::FILLED, 'Vyberte akci');

        $this->addSubmit('submit_print', 'Propojit se skautIS akcí')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'formSubmitted');


    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $skautISEventID = $values['skautISEvent'];
        $this->presenter->dbsettings->set('skautis_seminar_id', $skautISEventID);

        $skautISEventName = '';

        foreach ($this->skautISEvents as $e) {
            if ($e->ID == $skautISEventID) {
                $skautISEventName = $e->DisplayName;
            }
        }
        $this->presenter->dbsettings->set('skautis_seminar_name', $skautISEventName);
        $this->presenter->flashMessage('Akce propojena', 'success');
        $this->presenter->redirect('this');
//    }

    }
}