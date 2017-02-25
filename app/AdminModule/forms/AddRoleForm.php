<?php

namespace App\AdminModule\ProgramModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use Nette;
use Nette\Application\UI\Form;

class AddRoleForm extends Nette\Object
{
    /** @var BaseForm */
    private $baseFormFactory;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(BaseForm $baseFormFactory, RoleRepository $roleRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->roleRepository = $roleRepository;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name', 'admin.acl.roles_name')
            ->addRule(Form::FILLED, 'admin.acl.roles_name_empty')
            ->addRule(Form::IS_NOT_IN, 'admin.acl.roles_name_exists', $this->roleRepository->findAllNames())
            ->addRule(Form::NOT_EQUAL, 'admin.acl.roles_name_reserved', 'test');

        $form->addSelect('parent', 'admin.acl.roles_parent', $this->roleRepository->getRolesWithoutRolesOptions([]))
            ->setPrompt('')
            ->setAttribute('title', $form->getTranslator()->translate('admin.acl.roles_parent_note'));

        $form->addSubmit('submit', 'admin.common.save');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        $role = new Role($values['name']);

        $parent = $this->roleRepository->findById($values['parent']);

        $role->setSystem(false);

        if ($parent) {
            foreach ($parent->getPermissions() as $permission)
                $role->addPermission($permission);

            foreach ($parent->getIncompatibleRoles() as $incompatibleRole)
                $role->addIncompatibleRole($incompatibleRole);

            foreach ($parent->getRequiredRoles() as $requiredRole)
                $role->addRequiredRole($requiredRole);

            foreach ($parent->getRegisterableCategories() as $registerableCategory)
                $role->addRegisterableCategory($registerableCategory);

            foreach ($parent->getPages() as $page)
                $role->addPage($page);

            $role->setFee($parent->getFee());
            $role->setCapacity($parent->getCapacity());
            $role->setApprovedAfterRegistration($parent->isApprovedAfterRegistration());
            $role->setSyncedWithSkautIS($parent->isSyncedWithSkautIS());
            $role->setRegisterable($parent->isRegisterable());
            $role->setRegisterableFrom($parent->getRegisterableFrom());
            $role->setRegisterableTo($parent->getRegisterableTo());
            $role->setDisplayArrivalDeparture($parent->isDisplayArrivalDeparture());
        }
        else {
            $nonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            foreach ($nonregistered->getPages() as $page)
                $role->addPage($page);
        }

        $this->roleRepository->save($role);
    }
}
