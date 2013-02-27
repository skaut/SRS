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

namespace SRS\Form\CMS;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class NewsForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addText('published', 'Zveřejněno:')
            ->addRule(Form::FILLED, 'Zadejte datum zveřejnění')->getControlPrototype()->class('datepicker');
        $this->addTextArea('text', 'Text:')
            ->addRule(Form::FILLED, 'Zadejte text')
            ->getControlPrototype()->class('tinyMCE');

//        $this->addText('valid_from', 'Platné od:');
//        $this->addText('valid_to', 'Platné do:');

        $this->addSubmit('submit','Uložit')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue','Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'formSubmitted');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $exists = $values['id'] != null;

        if (!$exists) {
            $news = new \SRS\Model\CMS\News();
        }
        else {
            $news = $this->presenter->context->database->getRepository('\SRS\model\CMS\News')->find($values['id']);
        }

        $news->setProperties($values, $this->presenter->context->database);

        if (!$exists) {
            $this->presenter->context->database->persist($news);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Aktualita upravena', 'success');
        $submitName = ($this->isSubmitted());
        $submitName = $submitName->htmlName;

        if ($submitName == 'submit_continue') $this->presenter->redirect('this', $news->id);
        $this->presenter->redirect(':Back:CMS:News:default');

    }

}