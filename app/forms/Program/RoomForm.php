<?php

namespace SRS\Form\Program;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro vytvoreni mistnosti
 */
class RoomForm extends \SRS\Form\EntityForm
{
    protected $dbsettings;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $user;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings, $em, $user)
    {
        parent::__construct($parent, $name);

        $this->dbsettings = $dbsettings;
        $this->em = $em;
        $this->user = $user;

        $this->addText('name', 'Název:')
            ->addRule(Form::FILLED, 'Zadejte název');
        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary space');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $room = new \SRS\Model\Program\Room();
        $room->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->persist($room);
        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Místnost přidána', 'success');

        $this->presenter->redirect(':Back:Program:Room:list');
    }
}