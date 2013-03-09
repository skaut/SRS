<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.1.13
 * Time: 17:54
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Form\CMS;
use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class PageForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $roleChoices, $activeArea = null)
    {
        parent::__construct($parent, $name);
        $this->addHidden('id');
        $this->addText('name', 'Jméno stránky:')
            ->addRule(Form::FILLED, 'Zadejte jméno')
            ->getControlPrototype()->class('name');
        $this->addCheckbox('public', 'Zveřejněno:');
        $this->addText('slug','Slug:')
            ->addRule(Form::FILLED, 'Zadejte slug')
            ->getControlPrototype()->class('slug');
        $this->addMultiSelect('roles', 'Viditelná pro Role:')->setItems($roleChoices)->getControlPrototype()->class('multiselect');
        $this->addSelect('add_content', 'Přidat obsah', \SRS\Model\CMS\Content::$TYPES)->setPrompt('vyber typ');
        $this->addSubmit('submit_content', 'OK')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right btn-primary');
        $this->addSubmit('submit_to_list', 'Uložit')->getControlPrototype()->class('btn pull-right btn-primary');
        $this->addSubmit('submit_to_sidebar', 'Postranní lišta')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_to_main', 'Hlavní')->getControlPrototype()->class('btn');

        // Formularove prvky tykajici se jednotlivych obsahu se pridavaji v Page Presenteru v metode createComponent

    }

    //zpracovani formulare v Page presenteru
}
