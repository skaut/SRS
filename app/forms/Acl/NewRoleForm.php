<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
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
            $roles[$role->id] =  $role->name;
        }

        $this->addText('name', 'Jméno role:')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addSelect('parent','Založit roli na:', $roles)->setPrompt("žádný")->getControlPrototype()->title('Tato informace se použije pouze pro nastavení počátečních práv pro tuto roli.');
        $this->addSubmit('submit','Vytvořit roli')->getControlPrototype()->class('btn');

        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        if ($this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findBy(array('name'=>$values['name'])) == null) {
        $parentRole = null;

        if (isset($values['parent'])) {
            $parentRole = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->find($values['parent']);
        }

        $newRole = new \SRS\Model\Acl\Role($values['name']);
        $newRole->system = false;
        if ($parentRole != null) {
            foreach ($parentRole->permissions as $perm) {
                $newRole->permissions->add($perm);
            }
        }

        $this->presenter->context->database->persist($newRole);
        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Role vytvořena', 'success');
        $this->presenter->redirect('editRole', array('id' => $newRole->id));
        }

        else {
            $this->presenter->flashMessage('Role s tímto jménem již existuje', 'error');
        }
    }

}