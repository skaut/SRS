<?php

namespace SRS\Form\Program;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro vytvoreni mistnosti
 */
class CategoryForm extends \SRS\Form\EntityForm
{
    protected $dbsettings;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $user;

    public function __construct(IContainer $parent = NULL, $name = NULL, $dbsettings, $em, $user)
    {
        parent::__construct($parent, $name);

        $this->dbsettings = $dbsettings;
        $this->em = $em;
        $this->user = $user;

        $roles = $this->em->getRepository('\SRS\Model\Acl\Role')->findRegisterable();

        $checkRolesCount = function($field) {
            $values = $this->getComponent('registerableRoles')->getRawValue();

            if (count($values) == 0)
                return false;
            return true;
        };

        $this->addHidden('id');

        $this->addText('name', 'Název')
            ->addRule(Form::FILLED, 'Zadejte název');

        $this->addMultiSelect('registerableRoles', 'Role oprávněné k přihlášení')
            ->setItems(\SRS\Form\EntityForm::getFormChoices($roles, 'id', 'name'))
            ->addRule($checkRolesCount, 'Vyberte alespoň jednu roli')
            ->getControlPrototype()->class('multiselect');


        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');

        $this->onSuccess[] = callback($this, 'submitted');
        $this->onError[] = callback($this, 'error');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $exists = $values['id'] != null;

        $formValuesRegisterableRoles = $this->getComponent('registerableRoles')->getRawValue(); //oklika
        $values['registerableRoles'] = $formValuesRegisterableRoles;

        if (!$exists) {
            $category = new \SRS\Model\Program\Category();
        } else {
            $category = $this->presenter->context->database->getRepository('\SRS\model\Program\Category')->find($values['id']);
        }

        $category->setProperties($values, $this->presenter->context->database);

        if (!$exists) {
            $this->presenter->context->database->persist($category);
        }

        $this->presenter->context->database->flush();

        $this->presenter->flashMessage('Záznam uložen', 'success');
        $this->presenter->redirect(':Back:Program:Category:list');
    }

    public function error()
    {
        foreach ($this->getErrors() as $error) {
            $this->presenter->flashMessage($error, 'error');
        }
    }
}