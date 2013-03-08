<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


namespace SRS\Form\Configuration;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;


class SkautISEventForm extends UI\Form
{

    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $skautISEvents)
    {
        parent::__construct($parent, $name);

        $this->addSelect('skautISEvent', 'Vyberte akci ze skautIS')->setItems(\SRS\Form\EntityForm::getFormChoices($skautISEvents, 'ID', 'DisplayName'));
        $this->addSubmit('submit_print','Uložit')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');


    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $skautISEventID = $values['skautISEvent'];
        $this->presenter->dbsettings->set('skautis_seminar_id', $skautISEventID);
        $this->presenter->flashMessage('Akce propojena', 'success');
        $this->presenter->redirect('this');
//    }

    }
}