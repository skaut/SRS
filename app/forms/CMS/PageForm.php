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
    public function __construct(IContainer $parent = NULL, $name = NULL, $roleChoices)
    {
        parent::__construct($parent, $name);
        $this->addHidden('id');
        $this->addText('name', 'Jméno stránky:')->getControlPrototype()->class('name')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addCheckbox('public', 'Zveřejněno:');
        $this->addText('slug','Slug:')->getControlPrototype()->class('slug')
            ->addRule(Form::FILLED, 'Zadejte slug');
        $this->addMultiSelect('roles', 'Viditelná pro Role:')->setItems($roleChoices);
        $this->addSelect('add_content', 'Přidat obsah', \SRS\Model\CMS\Content::$TYPES)->setPrompt('vyber typ');
        $this->addSubmit('submit_content', 'OK')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right btn-primary');
        $this->addSubmit('submit_to_list', 'Uložit')->getControlPrototype()->class('btn pull-right btn-primary');

        // Formularove prvky tykajici se jednotlivych obsahu se pridavaji v CMS Presenteru v metode createComponent
    }
}
