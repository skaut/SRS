<?php
/**
 * Date: 18.2.13
 * Time: 10:16
 * Author: Michal Májský
 */
namespace SRS\Form\Evidence;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro upravu udaju ucastnika na detailu
 */
class EvidenceEditForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $configParams, $database, $dbsettings)
    {
        parent::__construct($parent, $name);

        $roles = $database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $rolesGrid = array();
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST && $role->name != Role::UNAPPROVED) {
                $rolesGrid[$role->id] = $role->name;
            }
        }

        $this->addHidden('id');

        $checkRolesCapacity = function($field, $args) {
            $database = $args[0];
            $approved = $args[1];

            if ($approved->getValue() === false)
                return true;

            $values = $this->getComponent('roles')->getRawValue();
            $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->usersLimit !== null) {
                    if (($role->countVacancies() == 0 && !$user->isInRole($role->name)) || ($role->countVacancies() == 0 && !$user->approved))
                        return false;
                }
            }
            return true;
        };

        $checkRolesCombination = function($field, $database) {
            $values = $this->getComponent('roles')->getRawValue();

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->name == Role::REGISTERED && count($values) != 1)
                    return false;
            }
            return true;
        };

        $checkRolesEmpty = function($field) {
            $values = $this->getComponent('roles')->getRawValue();
            return count($values) != 0;
        };

        $approved = $this->addCheckbox('approved', 'Schválený');

        $this->addMultiSelect('roles', 'Role')->setItems($rolesGrid)
            ->setAttribute('size', count($rolesGrid))
            ->addRule($checkRolesCapacity, 'Kapacita role byla překročena.', [$database, $approved])
            ->addRule($checkRolesCombination, 'Role "Nepřihlášený" nemůže být kombinována s jinou rolí.', $database)
            ->addRule($checkRolesEmpty, 'Musí být přidělena alespoň jedna role.', $database)
            ->getControlPrototype()->class('multiselect');

        $this->addCheckbox('attended', 'Přítomen');

        $this->addSelect('paymentMethod', 'Platební metoda')->setItems($configParams['payment_methods'])->setPrompt('Nezadáno');

        $this->addText('variableSymbol', 'Variabilní symbol')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER);

        $this->addText('paymentDate', 'Zaplaceno dne')
            ->getControlPrototype()->class('datepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum zaplacení není ve správném tvaru', \SRS\Helpers::DATE_PATTERN);

        $this->addText('incomeProofPrintedDate', 'Příjmový doklad vytištěn dne')
            ->getControlPrototype()->class('datepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum vytištění příjmového dokladu není ve správném tvaru', \SRS\Helpers::DATE_PATTERN);

        $this->addText('arrival', 'Příjezd')
            ->setAttribute('class', 'datetimepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas příjezdu není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $this->addText('departure', 'Odjezd')
            ->setAttribute('class', 'datetimepicker')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas odjezdu není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);


        $CUSTOM_BOOLEAN_COUNT = $configParams['user_custom_boolean_count'];
        for ($i = 0; $i < $CUSTOM_BOOLEAN_COUNT; $i++) {
            $column = $dbsettings->get('user_custom_boolean_' . $i);
            $propertyName = 'customBoolean' . $i;
            $this->addCheckbox($propertyName, $column);
        }

        $CUSTOM_TEXT_COUNT = $configParams['user_custom_text_count'];
        for ($i = 0; $i < $CUSTOM_TEXT_COUNT; $i++) {
            $column = $dbsettings->get('user_custom_text_' . $i);
            $propertyName = 'customText' . $i;
            $this->addText($propertyName, $column);
        }

        $this->addTextArea('note', 'Neveřejné poznámky');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);

        $formValuesRoles = $this->getComponent('roles')->getRawValue(); //oklika

        $roles = array();
        foreach ($formValuesRoles as $roleId) {
            $roles[] = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));
        }

        $user->changeRolesTo($roles);

        $code = $this->presenter->context->database->getRepository('\SRS\Model\Settings')->get('variable_symbol_code');
        if ($user->generateVariableSymbol($code) == $values['variableSymbol'])
            $values['variableSymbol'] = null;

        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Záznam uložen', 'success');
        $this->presenter->redirect('this');
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}
