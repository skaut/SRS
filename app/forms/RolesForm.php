<?php
namespace SRS\Form;

use Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro upravu roli udaju ucastnika
 */
class RolesForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $database, $user)
    {
        parent::__construct($parent, $name);

        $roles = $database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();

        $rolesGrid = array();
        $availableRoles = array();
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST && $role->name != Role::UNAPPROVED) {
                if ($role->usersLimit === null)
                    $rolesGrid[$role->id] = $role->name;
                else
                    $rolesGrid[$role->id] = "{$role->name} (obsazeno {$role->countApprovedUsersInRole()}/{$role->usersLimit})";
                $availableRoles[] = $role;
            }
        }
        foreach ($user->roles as $role) {
            if ($role->name != Role::GUEST && $role->name != Role::UNAPPROVED && $role->name != Role::REGISTERED) {
                if (!in_array($role, $availableRoles)) {
                    if ($role->usersLimit === null)
                        $rolesGrid[$role->id] = $role->name;
                    else
                        $rolesGrid[$role->id] = "{$role->name} (obsazeno {$role->countApprovedUsersInRole()}/{$role->usersLimit})";
                    $availableRoles[] = $role;
                }
            }
        }


        $this->addHidden('id');

        $checkRolesCapacity = function($field, $database) {
            $values = $this->getComponent('roles')->getRawValue();
            $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->usersLimit !== null) {
                    if ($role->countVacancies() == 0 && !$user->isInRole($role->name))
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
            $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $this->getForm()->getHttpData()['id']));

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if (!$role->isRegisterableNow() && !$user->isInRole($role->name))
                    return false;
            }

            return true;
        };

        $rolesSelect = $this->addMultiSelect('roles', 'Role ')->setItems($rolesGrid)
            ->addRule($checkRolesCapacity, 'Všechna místa v některé roli jsou obsazena.', $database)
            ->addRule($checkRolesEmpty, 'Musí být vybrána alespoň jedna role.', $database)
            ->addRule($checkRolesRegisterable, 'Registrace do některé z rolí již není možná.', $database);


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
                $rolesSelect->addRule($checkIncompatibleRoles, 'Není možné kombinovat roli ' . $messageThis . ' s rolemi: ' . $messageOthers . '.', [$database, $role]);
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
                $rolesSelect->addRule($checkRequiredRoles, 'K roli ' . $messageThis . ' musíte mít vybrané role: ' . $messageOthers . '.', $role);
            }
        }

        $rolesSelect->getControlPrototype()->class('multiselect');

        $this->addSubmit('submit', 'Upravit role');
        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $values['id']));

        $formValuesRoles = $this->getComponent('roles')->getRawValue(); //oklika

        $roles = array();
        foreach ($formValuesRoles as $roleId) {
            $roles[] = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));
        }

        $approved = $user->approved;
        foreach ($roles as $role) {
            if (!$user->roles->contains($role))
                if (!$role->approvedAfterRegistration)
                    $approved = false;
        }

        $user->changeRolesTo($roles);
        $user->approved = $approved;

        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Role byly upraveny, přihlaste se prosím znovu.', 'success');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}
