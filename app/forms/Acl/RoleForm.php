<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
 */




namespace SRS\Form;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
  * Formular pro editaci jiz existujici role
  * Zbyvajici parametry pro roli se zadavi v RoleForm.php
  *
 */
class RoleForm extends EntityForm
{

    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addText('name', 'Jméno role:')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addCheckbox('registerable', 'Registrovatelná');
        $this->addText('registerableFrom', 'Registrovatelná od')->getControlPrototype()->class('datepicker');
        $this->addText('registerableTo', 'Registrovatelná do')->getControlPrototype()->class('datepicker');

        $this->addMultiSelect('permissions', 'Práva');
        $this->addSubmit('submit','Upravit roli')->getControlPrototype()->class('btn');
        $this->addSubmit('submit_continue','Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues(); // POZOR!! getValues z nejakeho neznameho duvodu nevraci permissions, ziskavame oklikou
        $role = $this->presenter->roleRepo->find($values['id']);

        $formValuesPerms = $this->getComponent('permissions')->getRawValue(); //oklika
        $values['permissions'] = $formValuesPerms;


        if ($values['registerableTo'] != null && ($values['registerableTo'] < $values['registerableFrom'] && $values['registerableFrom'] != null)) {
            $this->presenter->flashMessage('Datum do musí být větší než od', 'error');
        }

        else {
            $role->setProperties($values, $this->presenter->context->database);
            //doctrine z nejakyho duvodu cpe do data dnesek ikdyz ma null
            if ($values['registerableFrom'] == null) {
                $role->registerableFrom = null;
            }
            if ($values['registerableTo'] == null) {
                $role->registerableTo = null;
            }
            $this->presenter->context->database->flush();
            $this->presenter->flashMessage('Role upravena', 'success');
            $submitName = ($this->isSubmitted());
            $submitName = $submitName->htmlName;

           if ($submitName == 'submit_continue') $this->presenter->redirect('this');
           $this->presenter->redirect('Acl:roles');
        }

    }

}