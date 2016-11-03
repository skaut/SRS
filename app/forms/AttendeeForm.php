<?php
/**
 * Date: 7.12.12
 * Time: 9:30
 * Author: Michal Májský
 */
namespace SRS\Form;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro prihlasku
 */
class AttendeeForm extends EntityForm
{
    protected $dbsettings;

    protected $database;

    protected $configParams;


    public function __construct(IContainer $parent = NULL, $name = NULL, $translator, $configParams, $dbsettings, $database, $user)
    {
        parent::__construct($parent, $name);

        $this->dbsettings = $dbsettings;
        $this->database = $database;
        $this->configParams = $configParams;

        $this->setTranslator($translator);

        $this->addHidden('id');

        $inputSex = $this->addSelect('sex', 'Pohlaví', ['male' => 'Muž', 'female' => 'Žena'])
            ->addRule(Form::FILLED, 'Zadejte pohlaví');

        $inputFirstName = $this->addText('firstName', 'Jméno')
            ->addRule(Form::FILLED, 'Zadejte jméno');

        $inputLastName = $this->addText('lastName', 'Příjmení')
            ->addRule(Form::FILLED, 'Zadejte příjmení');

        $inputNickName = $this->addText('nickName', 'Přezdívka');

        $inputBirthdate = $this->addText('birthdate', 'Datum narození')
            ->addRule(Form::FILLED, 'Zadejte datum narození');

        if ($user->member) {
            $inputSex->setDisabled()->setDefaultValue($user->sex);
            $inputFirstName->setDisabled()->setDefaultValue($user->firstName);
            $inputLastName->setDisabled()->setDefaultValue($user->lastName);
            $inputNickName->setDisabled()->setDefaultValue($user->nickName);
            $inputBirthdate->setDisabled()->setDefaultValue($user->birthdate->format("d.m.Y"));
        }
        else {
            $inputBirthdate->getControlPrototype()->class('datepicker-birthdate');
        }

//        @TODO - pro aktualizaci emailu je treba udelit zvlastni pravo, ktere SRS zatim nema
//        $this->addText('email', 'Email:')
//            ->addRule(Form::FILLED, 'Zadejte e-mailovou adresu')
//            ->addRule(Form::EMAIL, 'E-mail není ve správném tvaru');

        $this->addText('street', 'Ulice')
            ->addRule(Form::FILLED, 'Zadejte Ulici');

        $this->addText('city', 'Město')
            ->addRule(Form::FILLED, 'Zadejte Město');

        $this->addText('postcode', 'PSČ')
            ->addRule(Form::FILLED, 'Zadejte PSČ');

        $this->addText('state', 'Stát')
            ->addRule(Form::FILLED, 'Zadejte stát');

        $this->addCustomFields();

        $checkRolesCapacity = function($field, $database) {
            $values = $this->getComponent('roles')->getRawValue();

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->usersLimit !== null) {
                    if ($role->countVacancies() == 0)
                        return false;
                }
            }
            return true;
        };

        $checkIncompatibleRoles = function($field, $args) {
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

        $checkRequiredRoles = function($field, $role) {
            $values = $this->getComponent('roles')->getRawValue();

            if (!in_array($role->id, $values))
                return true;

            $requiredRoles = $role->getAllRequiredRoles();
            foreach ($requiredRoles as $requiredRole) {
                if (!in_array($requiredRole->id, $values))
                    return false;
            }

            return true;
        };

        $checkRolesEmpty = function($field) {
            $values = $this->getComponent('roles')->getRawValue();
            return count($values) != 0;
        };

        $checkRolesRegisterable = function($field, $database) {
            $values = $this->getComponent('roles')->getRawValue();

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if (!$role->isRegisterableNow())
                    return false;
            }

            return true;
        };

        $rolesSelect = $this->addMultiSelect('roles', 'front.attendeeForm.rolesLabel')
            ->addRule($checkRolesCapacity, 'Všechna místa v některé roli jsou obsazena.', $this->database)
            ->addRule($checkRolesEmpty, 'Musí být vybrána alespoň jedna role.', $this->database)
            ->addRule($checkRolesRegisterable, 'Registrace do některé z rolí již není možná.', $this->database);

        $roles = $this->database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();

        foreach($roles as $role) {
            $incompatibleRoles = $role->incompatibleRoles;
            $incompatibleRolesCount = count($incompatibleRoles);

            if ($incompatibleRolesCount > 0) {
                $messageThis = $role->name;

                $first = true;
                $messageOthers = "";
                foreach ($incompatibleRoles as $incompatibleRole) {
                    if ($incompatibleRole->isRegisterableNow()) {
                        if ($first)
                            $messageOthers .= $incompatibleRole->name;
                        else
                            $messageOthers .= ", " . $incompatibleRole->name;
                    }
                    $first = false;
                }
                $rolesSelect->addRule($checkIncompatibleRoles, $translator->translate('front.messages.incompatibleRolesSelected', NULL, ['role' => $messageThis, 'incompatibleRoles' => $messageOthers]), [$this->database, $role]);
            }

            $requiredRoles = $role->getAllRequiredRoles();
            $requiredRolesCount = count($requiredRoles);

            if ($requiredRolesCount > 0) {
                $messageThis = $role->name;

                $first = true;
                $messageOthers = "";
                foreach ($requiredRoles as $requiredRole) {
                    if ($first)
                        $messageOthers .= $requiredRole->name;
                    else
                        $messageOthers .= ", " . $requiredRole->name;
                    $first = false;
                }
                $rolesSelect->addRule($checkRequiredRoles, $translator->translate('front.messages.requiredRolesNotSelected', NULL, ['role' => $messageThis, 'requiredRoles' => $messageOthers]), $role);
            }
        }

        $departureArrivalVisibleRoles = $this->database->getRepository('\SRS\Model\Acl\Role')->findArrivalDepartureVisibleRoles();
        $ids = array();
        foreach ($departureArrivalVisibleRoles as $departureArrivalVisibleRole) {
            $ids[] = "".$departureArrivalVisibleRole->id;
        }

        $rolesSelect->addCondition('SRS\Form\AttendeeForm::toggleArrivalDeparture', $ids)
            ->toggle('arrivalInput')
            ->toggle('departureInput');

        $rolesSelect->getControlPrototype()->class('multiselect');

        $this->addText('arrival', 'Příjezd')
            ->setAttribute('class', 'datetimepicker')
            ->setOption('id', 'arrivalInput')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas příjezdu není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);

        $this->addText('departure', 'Odjezd')
            ->setAttribute('class', 'datetimepicker')
            ->setOption('id', 'departureInput')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'Datum a čas odjezdu není ve správném tvaru', \SRS\Helpers::DATETIME_PATTERN);


        $this->addCheckbox('agreement', 'Souhlasím, že uvedené údaje budou poskytnuty lektorům pro účely semináře')
            ->addRule(Form::FILLED, 'Musíte souhlasit s poskytnutím údajů');

        $this->addSubmit('submit', 'Registrovat');

        $this->onSuccess[] = callback($this, 'submitted');
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
            if (!$role->approvedAfterRegistration)
                $approved = false;
        }

        $user->setProperties($values, $this->presenter->context->database);
        $user->approved = $approved;

        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Registrace odeslána. Pro další informace o stavu registrace, platbě a semináři se musíte znovu přihlásit.', 'success forever');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');
    }

    public static function toggleArrivalDeparture(\Nette\Forms\IControl $control)
    {
        return false;
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


