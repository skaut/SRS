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

class NewPageForm extends UI\Form
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addText('name', 'Jméno stránky:')->getControlPrototype()->class('name')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addText('slug','Slug:')->getControlPrototype()->class('slug')
            ->addRule(Form::FILLED, 'Zadejte slug');
        $this->addSubmit('submit','Vytvořit Stránku')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        $page = new \SRS\Model\CMS\Page($values['name'], $values['slug']);
        $pageRepo =  $this->presenter->context->database->getRepository('\SRS\Model\CMS\Page');


        $page->position = $pageRepo->getCount();
        $slugExists = true;
        $i=0;
        $newSlug = $page->slug;
        while($slugExists) {
            $slugExists = $pageRepo->findBySlug($newSlug);
            $i++;
            if ($slugExists) $newSlug = $page->slug . $i;
        }

        $page->slug = $newSlug;

        // defaultni stav je, ze je stranka pro všechny role viditelná
        $roles = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findAll();
        foreach ($roles as $role) {
            $page->roles->add($role);
        }

        $this->presenter->context->database->persist($page);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Stránka vytvořena', 'success');
        $this->presenter->redirect(':Back:CMS:page', $page->id);
    }

}