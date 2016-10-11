<?php
/**
 * Date: 7.12.12
 * Time: 9:30
 * Author: Michal Májský
 */
namespace SRS\Form;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro prihlasku
 */
class AttendeeForm extends ProfileForm
{
    protected $roles;

    protected $dbsettings;

    protected $database;

    protected $configParams;


    public function __construct(IContainer $parent = NULL, $name = NULL, $roles, $configParams, $dbsettings, $database)
    {
        $this->roles = $roles;
        $this->dbsettings = $dbsettings;
        $this->database = $database;
        $this->configParams = $configParams;
        parent::__construct($parent, $name);

    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);

        $formValuesRoles = $this->getComponent('roles')->getRawValue(); //oklika
        $values['roles'] = $formValuesRoles;

        $user->removeRole(Role::REGISTERED);

        $approved = true;

        foreach ($values['roles'] as $roleId) {
            $role = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));
            $user->addRole($role);

            if (!$role->approvedAfterRegistration)
                $approved = false;
        }

        $user->setProperties($values, $this->presenter->context->database);
        $user->approved = $approved;

        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Přihláška odeslána. Více o stavu přihlášky se dozvíte opět na stránce s přihlašovacím formuláře.', 'success forever');
        $this->presenter->flashMessage('Pro další používání webu se znovu přihlašte přes skautIS', 'info forever');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');
    }

    public function setFields()
    {
        parent::setFields();
        $this->addCustomFields();

        $checkRolesCapacity = function($field, $database) {
            $values = $this->getComponent('roles')->getRawValue();
            $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->usersLimit !== null) {
                    if ($role->usersLimit < count($role->users) || (!$user->isInRole($role->name) && $role->usersLimit == count($role->users)))
                        return false;
                }
            }
            return true;
        };

        $checkRolesCombination = function($field, $args) {
            $database = $args[0];
            $role = $args[1];

            $values = $this->getComponent('roles')->getRawValue();

            if (!in_array($role->id, $values))
                return true;

            foreach ($values as $value) {
                $testRole = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role != $testRole && in_array($testRole, $role->incompatibleRoles->getValues()))
                    return false;
            }

            return true;
        };

        $checkRolesEmpty = function($field) {
            $values = $this->getComponent('roles')->getRawValue();
            return count($values) != 0;
        };

        $rolesSelect = $this->addMultiSelect('roles', 'Přihlásit jako')
            ->addRule($checkRolesCapacity, 'Všechna místa v některé roli jsou obsazena.', $this->database)
            ->addRule($checkRolesEmpty, 'Musí být vybrána alespoň jedna role.', $this->database);

        $roles = $this->database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();

        foreach($roles as $role) {
            $incompatibleRoles = $role->incompatibleRoles;
            $incompatibleRolesSize = count($incompatibleRoles);

            if ($incompatibleRolesSize > 0) {
                $message = $role->name;

                foreach ($incompatibleRoles as $incompatibleRole) {
                    if ($incompatibleRole->isRegisterableNow())
                        $message .= ", " . $incompatibleRole->name;
                }
                $rolesSelect->addRule($checkRolesCombination, 'Není možné kombinovat role: ' . $message . '.', [$this->database, $role]);
            }
        }

        $rolesSelect->getControlPrototype()->class('multiselect');

        $this->addCheckbox('agreement', 'Souhlasím, že uvedené údaje budou poskytnuty lektorům pro účely semináře')
            ->addRule(Form::FILLED, 'Musíte souhlasit s poskytnutím údajů');

        $this->addSubmit('submit', 'Přihlásit na seminář');
    }

    protected function configure()
    {
        $this->setFields();
    }

    protected function addCustomFields()
    {
        $customBooleanCount = $this->configParams['user_custom_boolean_count'];
        $customTextCount = $this->configParams['user_custom_text_count'];
        for ($i = 0; $i < $customBooleanCount; $i++) {
            $settingsColumn = 'user_custom_boolean_' . $i;
            //$dbColumn = 'customBoolean'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            if ($dbvalue != '') {
                $this->addCheckbox('customBoolean' . $i, $dbvalue);
            }
        }

        for ($i = 0; $i < $customTextCount; $i++) {
            $settingsColumn = 'user_custom_text_' . $i;
            //$dbColumn = 'customText'.$i;
            $dbvalue = $this->dbsettings->get($settingsColumn);
            if ($dbvalue != '') {
                $this->addText('customText' . $i, $dbvalue);
            }
        }
    }
}
