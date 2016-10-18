<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Program;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    Nette\Utils\Html;

/**
 * Formular pro editaci a vytvareni programoveho bloku
 */
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
        $rooms = $this->em->getRepository('\SRS\Model\Program\Room')->findAll();
        $categories = $this->em->getRepository('\SRS\Model\Program\Category')->findAll();


        $this->addHidden('id');

        $this->addText('name', 'Název')
            ->addRule(Form::FILLED, 'Zadejte název');

        $this->addSelect('category', 'Kategorie')
            ->setItems(\SRS\Form\EntityForm::getFormChoices($categories, 'id', 'name'))->setPrompt('-- vyberte --');

        $this->addText('capacity', 'Kapacita')
            ->addRule(Form::FILLED, 'Zadejte kapacitu')
            ->addRule(Form::INTEGER, 'Kapacita je číslo od 1 do x')
            ->getControlPrototype()->class('number');

        $this->addText('tools', 'Pomůcky');

        $this->addSelect('room', 'Místnost')
            ->setItems(\SRS\Form\EntityForm::getFormChoices($rooms, 'id', 'name'))->setPrompt('-- vyberte --');

        $this->addSelect('duration', 'Doba trvání')
            ->setItems($this->prepareDurationChoices())
            ->addRule(Form::FILLED, 'Zadejte dobu trvání');

        $this->addTextArea('perex', 'Stručný popis (160 znaků)')->getControlPrototype()->class('wide');

        $this->addTextArea('description', 'Detailní popis')->getControlPrototype()->class('tinyMCE wide');

        $this->addSelect('lector', 'Lektor');
        if ($this->user->isAllowed('Program', 'Spravovat Všechny Programy')) {
            $this['lector']->setItems(\SRS\Form\EntityForm::getFormChoices($lectors, 'id', 'displayName'))->setPrompt('-- vyberte --');
        } else {
            $this['lector']->setItems(array($this->user->id => $this->user->identity->object->displayName));
        }

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right space');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $exists = $values['id'] != null;

        if (!$exists) {
            $block = new \SRS\Model\Program\Block();
        } else {
            $block = $this->presenter->context->database->getRepository('\SRS\model\Program\Block')->find($values['id']);
        }

        $block->setProperties($values, $this->presenter->context->database);

        if (!$exists) {
            $this->presenter->context->database->persist($block);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Programový blok upraven', 'success');
        $submitName = ($this->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_continue') $this->presenter->redirect('this', array('id' => $block->id));
        $this->presenter->redirect(':Back:Program:Block:list');

    }

    protected function prepareDurationChoices()
    {
        $basicDuration = $this->dbsettings->get('basic_block_duration');
        $durationChoices = array();

        for ($i = 1; $i * $basicDuration <= 240; $i++) {
            $durationChoices[$i] = $i * $basicDuration . ' minut';
        }
        return $durationChoices;
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}