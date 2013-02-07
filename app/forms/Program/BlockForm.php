<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */


/**
 * Formular pro vytvoreni nove role
 * Zbyvajici parametry pro roli se zadavi v RoleForm.php

 */

namespace SRS\Form\Program;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class BlockForm extends \SRS\Form\EntityForm
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
        $lectors = $this->em->getRepository('\SRS\Model\Acl\Role')->findApprovedUsersInRole('lektor');



        $this->addHidden('id');
        $this->addText('name', 'Název:')
            ->addRule(Form::FILLED, 'Zadejte název');
        $this->addText('capacity', 'Kapacita:')
            ->addRule(Form::FILLED, 'Zadejte kapacitu')
            ->addRule(Form::INTEGER, 'Kapacita je číslo od 1 do x')
            ->getControlPrototype()->class('number');
        $this->addTextArea('tools', 'Pomůcky:');
        $this->addText('location', 'Lokalita:');
        $this->addSelect('duration', 'Doba trvání:')
            ->setItems($this->prepareDurationChoices())
            ->addRule(Form::FILLED, 'Zadejte dobu trvání');
        $this->addSelect('lector', 'Lektor:');
        if ($this->user->isAllowed('Program', 'Spravovat Všechny Programy')) {
            $this['lector']->setItems(\SRS\Form\EntityForm::getFormChoices($lectors, 'id', 'lastName'))->setPrompt('-- vyberte --');
        }
        else {
            $this['lector']->setItems(array($this->user->id => $this->user->identity->object->lastName));
        }
        $this->addSubmit('submit','Uložit')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue','Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'formSubmitted');

    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $exists = $values['id'] != null;

        if (!$exists) {
            $block = new \SRS\Model\Program\Block();
        }
        else {
            $block = $this->presenter->context->database->getRepository('\SRS\model\Program\Block')->find($values['id']);
        }

        $block->setProperties($values, $this->presenter->context->database);

        if (!$exists) {
            $this->presenter->context->database->persist($block);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Block upraven', 'success');
        $submitName = ($this->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_continue') $this->presenter->redirect('this', array('id' => $block->id));
        $this->presenter->redirect(':Back:Block:list');

    }

    protected function prepareDurationChoices() {
        $basicDuration = $this->dbsettings->get('basic_block_duration');
        $durationChoices = array();


        for ($i = 1; $i < 10; $i++) {
            $durationChoices[$i] = $i*$basicDuration . ' minut';
        }
        return $durationChoices;
    }

}