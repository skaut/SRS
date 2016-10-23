<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro editaci jiz existujici role
 */
class RoleForm extends EntityForm
{

    public function __construct(IContainer $parent = NULL, $name = NULL, $database)
    {
        parent::__construct($parent, $name);

        $id = $this->addHidden('id');

        $this->addText('name', 'Jméno role')
            ->addRule(Form::FILLED, 'Zadejte jméno');

        $this->addCheckbox('registerable', 'Registrovatelná');

        $this->addText('registerableFrom', 'Registrovatelná od')
            ->getControlPrototype()->class('datetimepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $this->addText('registerableTo', 'Registrovatelná do')
            ->getControlPrototype()->class('datetimepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $checkRoleCapacity = function($field, $args) {
            $database = $args[0];
            $id = $args[1];

            $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $id->value));
            if ($role->countApprovedUsersInRole() > $field->getValue())
                return false;
            return true;
        };

        $capacity = $this->addText('usersLimit', 'Kapacita');
        $capacity->addCondition(FORM::FILLED)
            ->addRule(FORM::INTEGER, 'Kapacita role musí být číslo')
            ->addRule($checkRoleCapacity, 'Kapacita role nesmí být nižší než počet uživatelů v roli', [$database, $id]);
        $capacity->getControlPrototype()->class('number');

        $this->addCheckbox('approvedAfterRegistration', 'Je uživateli role po registraci automaticky schválena?');

        $this->addCheckbox('syncedWithSkautIS', 'Uživatelé v této roli jsou uvedeni jako účastníci ve skautIS');

        $this->addCheckbox('displayInList', 'Zobrazit v přehledu uživatelů');

        $this->addCheckbox('displayCapacity', 'Zobrazit kapacitu na webu');

        $this->addCheckbox('displayArrivalDeparture', 'Evidovat příjezd a odjezd');

        $this->addText('fee', 'Výše účastnického poplatku')
            ->getControlPrototype()->class('number')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'Výše poplatku musí být číslo');

        $this->addMultiSelect('permissions', 'Práva')->getControlPrototype()->class('multiselect');

        $this->addMultiSelect('pages', 'Viditelné stránky')->getControlPrototype()->class('multiselect');

        $this->addMultiSelect('incompatibleRoles', 'Neregistrovatelná s')->getControlPrototype()->class('multiselect');

        $this->addMultiSelect('requiredRoles', 'Související role')->getControlPrototype()->class('multiselect');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right space ');
        $this->addSubmit('submit_continue', 'Uložit a pokračovat v úpravách')->getControlPrototype()->class('btn pull-right');

        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues(); // POZOR!! getValues z nejakeho neznameho duvodu nevraci permissions, ziskavame oklikou
        $role = $this->presenter->roleRepo->find($values['id']);

        $oldIncompatibleRoles = $role->incompatibleRoles->getValues();

        $formValuesPerms = $this->getComponent('permissions')->getRawValue(); //oklika
        $values['permissions'] = $formValuesPerms;

        $formValuesPages = $this->getComponent('pages')->getRawValue(); //oklika
        $values['pages'] = $formValuesPages;

        $formValuesIncompatibleRoles = $this->getComponent('incompatibleRoles')->getRawValue(); //oklika
        $values['incompatibleRoles'] = $formValuesIncompatibleRoles;

        $formValuesRequiredRoles = $this->getComponent('requiredRoles')->getRawValue(); //oklika
        $values['requiredRoles'] = $formValuesRequiredRoles;

        if ($values['registerableTo'] != null && $values['registerableFrom'] != null &&
            \DateTime::createFromFormat("d.m.Y H:i", $values['registerableTo']) <= \DateTime::createFromFormat("d.m.Y H:i", $values['registerableFrom'])) {
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
            if ($values['usersLimit'] == null) {
                $role->usersLimit = null;
            }
            if ($values['fee'] == null) {
                $role->fee = null;
            }

            // mazani neregistrovatelnych roli z opacne strany
            $role->removeIncompatibleRoles($oldIncompatibleRoles);
            foreach($values['incompatibleRoles'] as $incompatibleRoleId) {
                $incompatibleRole = $this->presenter->roleRepo->find($incompatibleRoleId);
                $role->addIncompatibleRole($incompatibleRole);
            }

            if (!$this->areCompatible($role)) {
                $this->presenter->flashMessage('Související role nemůže být zároveň neregistrovatelná s touto rolí', 'error');
            }
            else {
                $this->presenter->context->database->flush();
                $this->presenter->flashMessage('Role upravena', 'success');
                $submitName = ($this->isSubmitted());
                $submitName = $submitName->htmlName;

                if ($submitName == 'submit_continue') $this->presenter->redirect('this');
                $this->presenter->redirect('Acl:list');
            }
        }
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }

    private function areCompatible($role) {
        foreach ($role->incompatibleRoles as $incompatibleRole) {
            foreach ($role->requiredRoles as $requiredRole) {
                //vybrana stejna role jako neregistrovatelna i souvisejici
                if ($incompatibleRole == $requiredRole)
                    return false;

                //role souvisi s roli, ktera neni s touto registrovatelna
                $allRequiredRoles = $requiredRole->getAllRequiredRoles();
                foreach ($allRequiredRoles as $allRequiredRole) {
                    if ($incompatibleRole == $allRequiredRole)
                        return false;
                }
            }

            //role je neregistrovatelna s roli, ktera s touto roli souvisi
            $incompatibleRequiredRoles = $incompatibleRole->getAllRequiredRoles();
            foreach ($incompatibleRequiredRoles as $incompatibleRequiredRole) {
                if ($incompatibleRequiredRole == $role)
                    return false;
            }
        }

        //nektera role pozadujici tuto roli, neni registrovatelna s roli pozadovanou touto roli
        $requiredByRoleRoles = $role->getAllRequiredByRole();
        $requiredRoles = $role->getAllRequiredRoles();
        foreach ($requiredByRoleRoles as $requiredByRoleRole) {
            foreach ($requiredByRoleRole->incompatibleRoles as $requiredByRoleIncompatibleRole) {
                foreach($requiredRoles as $requiredRole) {
                    if ($requiredByRoleIncompatibleRole == $requiredRole)
                        return false;
                }
            }
        }

        return true;
    }
}