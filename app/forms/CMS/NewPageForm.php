<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */



namespace SRS\Form\CMS;

use Nette\Application\UI,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;



/**
 * Formular pro vytvoreni nove stranky
 */
class NewPageForm extends UI\Form
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addText('name', 'Jméno stránky')
            ->addRule(Form::FILLED, 'Zadejte jméno')
            ->getControlPrototype()->class('name');
        $this->addText('slug', 'Cesta')
            ->addRule(Form::FILLED, 'Zadejte slug')
            ->getControlPrototype()->class('slug');
        $this->addSubmit('submit', 'Vytvořit stránku')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        $page = new \SRS\Model\CMS\Page($values['name'], $values['slug']);
        $pageRepo = $this->presenter->context->database->getRepository('\SRS\Model\CMS\Page');


        $page->position = $pageRepo->getCount();
        $slugExists = true;
        $i = 0;
        $newSlug = $page->slug;
        while ($slugExists) {
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
        $this->presenter->redirect(':Back:CMS:Page:page', $page->id, 'main');
    }

}