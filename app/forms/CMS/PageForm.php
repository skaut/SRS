<?php
/**
 * Date: 1.1.13
 * Time: 17:54
 * Author: Michal Májský
 */
namespace SRS\Form\CMS;
use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;


/**
 * Formular pro stranky
 */
class PageForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $roleChoices, $activeArea = null)
    {
        parent::__construct($parent, $name);
        $this->addHidden('id');
        $this->addText('name', 'Jméno stránky')
            ->addRule(Form::FILLED, 'Zadejte jméno')
            ->getControlPrototype()->class('name');
        $this->addCheckbox('public', 'Zveřejněno');
        $this->addText('slug', 'Cesta')
            ->addRule(Form::FILLED, 'Zadejte cestu')
            ->getControlPrototype()->class('slug');
        $this->addMultiSelect('roles', 'Viditelná pro role')->setItems($roleChoices)
            ->addRule(Form::FILLED, 'Zadejte alespoň jednu roli. Pokud chcete stránku skrýt. Použijte pole "Zveřejněno"');
        $this->addSelect('add_content', 'Přidat obsah', \SRS\Model\CMS\Content::$TYPES)->setPrompt('vyber typ');
        $this->addSubmit('submit_content', 'OK')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right');
        $this->addSubmit('submit_to_list', 'Uložit')->getControlPrototype()->class('btn pull-right btn-primary');
        $this->addSubmit('submit_to_sidebar', 'Postranní lišta')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_to_main', 'Hlavní')->getControlPrototype()->class('btn');

        $this['roles']->getControlPrototype()->class('multiselect');
        // Formularove prvky tykajici se jednotlivych obsahu se pridavaji v Page Presenteru v metode createComponent
    }

    //zpracovani formulare v Page presenteru
}
