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
    Nette\ComponentModel\IContainer;

class AttendeeForm extends ProfileForm
{
    protected $roles;

    protected $dbsettings;

    protected $configParams;


    public function __construct(IContainer $parent = NULL, $name = NULL, $roles, $configParams, $dbsettings)
    {
        $this->roles = $roles;
        $this->dbsettings = $dbsettings;
        $this->configParams = $configParams;
        parent::__construct($parent, $name);

    }

    public function submitted()
    {
        $values = $this->getValues();
        $role = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->find($values['role']);
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $user->approved = $role->approvedAfterRegistration;
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Přihláška odeslána', 'success');
        $this->presenter->flashMessage('Pro další používání webu se znovu přihlašte přes skautIS', 'info');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');

    }

    public function setFields()
    {
        parent::setFields();
        $this->addCustomFields();
        $this->addSelect('role', 'Přihlásit jako:')->setItems($this->roles)
            ->addRule(Form::FILLED, 'Vyplňte roli');
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
