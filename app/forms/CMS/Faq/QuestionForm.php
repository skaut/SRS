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

class QuestionForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addTextArea('question', 'Otázka:')
            ->addRule(Form::FILLED, 'Zadejte otázku');

        $this->addSubmit('submit','Položit dotaz')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        $faq = new \SRS\Model\CMS\Faq();
        $faq->question = $values['question'];
        $this->presenter->context->database->persist($faq);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Otázka položena. Bude zveřejněna spolu s odpovědí dle uvážení organizátorů');
        $this->presenter->redirect('this');


    }

}