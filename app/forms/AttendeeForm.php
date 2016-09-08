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
        $user->removeRole(Role::REGISTERED);
        $role = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $values['role']));
        $user->addRole($role);
        $user->setProperties($values, $this->presenter->context->database);
        $user->approved = $role->approvedAfterRegistration;
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

        $checkRoleCapacity = function($field, $database) {
            $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $field->getValue()));
            if ($role->usersLimit !== null) {
                if ($role->usersLimit <= count($role->users))
                    return false;
            }
            return true;
        };

        $this->addSelect('role', 'Přihlásit jako:')->setItems($this->roles)
            ->addRule(Form::FILLED, 'Vyplňte roli')
            ->addRule($checkRoleCapacity, 'Překročen maximální počet účastníků v této roli', $this->database);
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
