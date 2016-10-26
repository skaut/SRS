<?php
namespace SRS\Form\Evidence;

use Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro hromadnou upravu roli ucastniku
 */
class EvidenceEditRolesForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $database)
    {
        parent::__construct($parent, $name);

        $roles = $database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $rolesGrid = array();
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST && $role->name != Role::UNAPPROVED) {
                if ($role->usersLimit !== null)
                    $rolesGrid[$role->id] = "{$role->name} (obsazeno {$role->countApprovedUsersInRole()}/{$role->usersLimit})";
                else
                    $rolesGrid[$role->id] = "{$role->name}";
            }
        }

        $ids = $this->addHidden('ids');

        $checkRolesCapacity = function($field, $args) {
            $values = $this->getComponent('roles')->getRawValue();

            $database = $args[0];
            $ids = explode(",", $args[1]->value);

            $usersCount = count($ids);

            foreach ($values as $roleId) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));

                if ($role->usersLimit !== null) {
                    $freeCapacity = $role->countVacancies();

                    foreach($ids as $userId) {
                        $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $userId));
                        if (!$user->approved || $user->isInRole($role->name))
                            $usersCount--;
                    }

                    if ($freeCapacity < $usersCount)
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

        $this->addMultiSelect('roles', 'Role')->setItems($rolesGrid)
            ->setAttribute('size', count($rolesGrid))
            ->addRule($checkRolesCapacity, 'Kapacita role byla překročena.', [$database, $ids])
            ->addRule($checkRolesCombination, 'Role "Nepřihlášený" nemůže být kombinována s jinou rolí.', $database)
            ->addRule($checkRolesEmpty, 'Musí být přidělena alespoň jedna role.', $database)
            ->getControlPrototype()->class('multiselect');

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $ids = explode(",", $values['ids']);

        $formValuesRoles = $this->getComponent('roles')->getRawValue(); //oklika

        $roles = array();
        foreach ($formValuesRoles as $roleId) {
            $roles[] = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));
        }

        foreach($ids as $id) {
            $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $id));
            $user->changeRolesTo($roles);
        }

        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Záznam uložen', 'success');
        $this->presenter->redirect(':Back:Evidence:list');
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}
