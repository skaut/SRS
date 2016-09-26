<?php
/**
 * Date: 18.2.13
 * Time: 10:16
 * Author: Michal Májský
 */
namespace SRS\Form\Evidence;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer,
    SRS\Model\Acl\Role;

/**
 * Formular pro upravu udaju ucastnika na detailu
 */
class EvidenceEditRolesForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $configParams, $database)
    {
        parent::__construct($parent, $name);

        $roles = $database->getRepository('\SRS\Model\Acl\Role')->findAll();
        $rolesGrid = array();
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST) {
                $rolesGrid[$role->id] = $role->name;
            }
        }

        $ids = $this->addHidden('ids');

        $checkRolesCapacity = function($field, $args) {
            $database = $args[0];
            $ids = explode(",", $args[1]->value);

            $usersCount = count($ids);

            foreach ($field->getValue() as $roleId) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));

                if ($role->usersLimit !== null) {
                    $freeCapacity = $role->usersLimit - count($role->users);

                    foreach($ids as $userId) {
                        $user = $database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $userId));
                        if ($user->isInRole($role->name))
                            $freeCapacity++;
                    }

                    if ($freeCapacity < $usersCount)
                        return false;
                }
            }
            return true;
        };

        $checkRolesCombination = function($field, $database) {
            $values = $field->getValue();

            foreach ($values as $value) {
                $role = $database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $value));
                if ($role->name == Role::REGISTERED && count($values) != 1)
                    return false;
            }
            return true;
        };

        $checkRolesEmpty = function($field) {
            $values = $field->getValue();
            return count($values) != 0;
        };

        $this->addMultiSelect('roles', 'Role')->setItems($rolesGrid)
            ->setAttribute('size', count($rolesGrid))
            ->addRule($checkRolesCapacity, 'Kapacita role byla překročena.', [$database, $ids])
            ->addRule($checkRolesCombination, 'Role "Nepřihlášený" nemůže být kombinována s jinou rolí.', $database)
            ->addRule($checkRolesEmpty, 'Musí být přidělena alespoň jedna role.', $database);

        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $ids = explode(",", $values['ids']);

        foreach($ids as $id) {
            $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->findOneBy(array('id' => $id));
            $user->removeAllRoles();

            foreach ($values['roles'] as $roleId) {
                $role = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('id' => $roleId));
                $user->addRole($role);
            }
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
