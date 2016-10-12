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
 * Formular pro vytvoreni nove role
 * Zbyvajici parametry pro roli se zadavi v RoleForm.php
 */
class NewRoleForm extends UI\Form
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $rolesAvailable)
    {
        parent::__construct($parent, $name);

        $roles = array();

        foreach ($rolesAvailable as $role) {
            $roles[$role->id] = $role->name;
        }

        $this->addText('name', 'Jméno role')
            ->addRule(Form::FILLED, 'Zadejte jméno');

        $this->addSelect('parent', 'Vychází z role', $roles)
            ->setPrompt("žádný")
            ->getControlPrototype()
            ->title('Tato informace se použije pouze pro nastavení počátečních práv pro tuto roli.');

        $this->addSubmit('submit', 'Vytvořit roli')->getControlPrototype()->class('btn btn-primary pull-right');

        $this->onSuccess[] = callback($this, 'formSubmitted');
        $this->onError[] = callback($this, 'error');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        if ($this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findBy(array('name' => $values['name'])) == null) {
            $parentRole = null;

            if (isset($values['parent'])) {
                $parentRole = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->find($values['parent']);
            }

            $newRole = new \SRS\Model\Acl\Role($values['name']);
            $newRole->system = false;

            if ($parentRole != null) {
                foreach ($parentRole->permissions as $permission) {
                    $newRole->permissions->add($permission);
                }

                foreach ($parentRole->incompatibleRoles as $incompatibleRole) {
                    $newRole->incompatibleRoles->add($incompatibleRole);
                }

                $newRole->registerableCategories = $parentRole->registerableCategories;
                $newRole->pages = $parentRole->pages;

                $newRole->pays = $parentRole->pays;
                $newRole->fee = $parentRole->fee;
                $newRole->usersLimit = $parentRole->usersLimit;
                $newRole->displayCapacity = $parentRole->displayCapacity;
                $newRole->displayInList = $parentRole->displayInList;
                $newRole->approvedAfterRegistration = $parentRole->approvedAfterRegistration;
                $newRole->syncedWithSkautIS = $parentRole->syncedWithSkautIS;
                $newRole->registerable = $parentRole->registerable;
                $newRole->registerableFrom = $parentRole->registerableFrom;
                $newRole->registerableTo = $parentRole->registerableTo;
                $newRole->displayArrivalDeparture = $parentRole->displayArrivalDeparture;
            }
            else {
                $roleRegistered = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => 'Nepřihlášený'));
                $newRole->pages = $roleRegistered->pages;
            }

            $this->presenter->context->database->persist($newRole);
            $this->presenter->context->database->flush();

            $this->presenter->flashMessage('Role vytvořena', 'success');
            $this->presenter->redirect('edit', array('id' => $newRole->id));
        } else {
            $this->presenter->flashMessage('Role s tímto jménem již existuje', 'error');
        }
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}