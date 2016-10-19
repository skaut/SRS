<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro editaci jiz existujici role
 */
class RoleForm extends EntityForm
{

    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');

        $this->addText('name', 'Jméno role')
            ->addRule(Form::FILLED, 'Zadejte jméno');

        $this->addCheckbox('registerable', 'Registrovatelná');

        $this->addText('registerableFrom', 'Registrovatelná od')
            ->getControlPrototype()->class('datetimepicker')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::PATTERN, 'Datum a čas není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $this->addText('registerableTo', 'Registrovatelná do')
            ->getControlPrototype()->class('datetimepicker')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::PATTERN, 'Datum a čas není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $this->addText('usersLimit', 'Kapacita')
            ->getControlPrototype()->class('number')
            ->addRule(FORM::INTEGER, 'Kapacita role musí být číslo');

        $this->addCheckbox('approvedAfterRegistration', 'Je uživateli role po registraci automaticky schválena?');

        $this->addCheckbox('syncedWithSkautIS', 'Uživatelé v této roli jsou uvedeni jako účastníci ve skautIS');

        $this->addCheckbox('displayInList', 'Zobrazit v přehledu uživatelů');

        $this->addCheckbox('displayCapacity', 'Zobrazit kapacitu na webu');

        $this->addCheckbox('displayArrivalDeparture', 'Evidovat příjezd a odjezd');

        $this->addCheckbox('pays', 'Platí za účast?');

        $this->addText('fee', 'Výše účastnického poplatku')
            //->setDefaultValue(0)
            ->getControlPrototype()->class('number')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::INTEGER, 'Výše poplatku musí být číslo');

        $this->addMultiSelect('permissions', 'Práva')->getControlPrototype()->class('multiselect');

        $this->addMultiSelect('pages', 'Viditelné stránky')->getControlPrototype()->class('multiselect');

        $this->addMultiSelect('incompatibleRoles', 'Neregistrovatelná s')->getControlPrototype()->class('multiselect');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right space ');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right');

        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues(); // POZOR!! getValues z nejakeho neznameho duvodu nevraci permissions, ziskavame oklikou
        $role = $this->presenter->roleRepo->find($values['id']);

        $formValuesPerms = $this->getComponent('permissions')->getRawValue(); //oklika
        $values['permissions'] = $formValuesPerms;

        $formValuesPages = $this->getComponent('pages')->getRawValue(); //oklika
        $values['pages'] = $formValuesPages;

        $oldIncompatibleRoles = $role->incompatibleRoles->getValues();

        $formValuesIncompatibleRoles = $this->getComponent('incompatibleRoles')->getRawValue(); //oklika
        $values['incompatibleRoles'] = $formValuesIncompatibleRoles;

        if ($values['registerableTo'] != null && ($values['registerableTo'] < $values['registerableFrom'] && $values['registerableFrom'] != null)) {
            $this->presenter->flashMessage('Datum do musí být větší než od', 'error');
        } else {
            $role->setProperties($values, $this->presenter->context->database);
            //doctrine z nejakyho duvodu cpe do data dnesek ikdyz ma null
            if ($values['registerableFrom'] == null) {
                $role->registerableFrom = null;
            }
            if ($values['registerableTo'] == null) {
                $role->registerableTo = null;
            }
            if ($values['usersLimit'] == null) {
                $role->usersLimit = null;
            }

            $role->removeAllIncompatibleRoles($oldIncompatibleRoles);

            foreach($values['incompatibleRoles'] as $incompatibleRoleId) {
                $incompatibleRole = $this->presenter->roleRepo->find($incompatibleRoleId);
                $role->addIncompatibleRole($incompatibleRole);
            }

            $this->presenter->context->database->flush();
            $this->presenter->flashMessage('Role upravena', 'success');
            $submitName = ($this->isSubmitted());
            $submitName = $submitName->htmlName;

            if ($submitName == 'submit_continue') $this->presenter->redirect('this');
            $this->presenter->redirect('Acl:list');
        }
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}