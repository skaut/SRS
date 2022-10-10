<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Services\AclService;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Formulář pro vytvoření role.
 */
class AddRoleFormFactory
{
    use Nette\SmartObject;

    public function __construct(private BaseFormFactory $baseFormFactory, private AclService $aclService, private RoleRepository $roleRepository)
    {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addText('name', 'admin.acl.roles_name')
            ->addRule(Form::FILLED, 'admin.acl.roles_name_empty')
            ->addRule(Form::IS_NOT_IN, 'admin.acl.roles_name_exists', $this->aclService->findAllRoleNames())
            ->addRule(Form::NOT_EQUAL, 'admin.acl.roles_name_reserved', 'test');

        $form->addSelect('parent', 'admin.acl.roles_parent', $this->aclService->getRolesWithoutRolesOptions([]))
            ->setPrompt('')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.acl.roles_parent_note'));

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     */
    public function processForm(Form $form, stdClass $values): void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $role = new Role($values->name);

        $parent = $this->roleRepository->findById($values->parent);

        $role->setSystemRole(false);

        if ($parent) {
            foreach ($parent->getPermissions() as $permission) {
                $role->addPermission($permission);
            }

            foreach ($parent->getIncompatibleRoles() as $incompatibleRole) {
                $role->addIncompatibleRole($incompatibleRole);
            }

            foreach ($parent->getRequiredRoles() as $requiredRole) {
                $role->addRequiredRole($requiredRole);
            }

            foreach ($parent->getRegisterableCategories() as $registerableCategory) {
                $role->addRegisterableCategory($registerableCategory);
            }

            foreach ($parent->getPages() as $page) {
                $role->addPage($page);
            }

            $role->setFee($parent->getFee());
            $role->setCapacity($parent->getCapacity());
            $role->setMinimumAge($parent->getMinimumAge());
            $role->setMaximumAge($parent->getMaximumAge());
            $role->setApprovedAfterRegistration($parent->isApprovedAfterRegistration());
            $role->setSyncedWithSkautIS($parent->isSyncedWithSkautIS());
            $role->setRegisterable($parent->isRegisterable());
            $role->setRegisterableFrom($parent->getRegisterableFrom());
            $role->setRegisterableTo($parent->getRegisterableTo());
        } else {
            $nonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            foreach ($nonregistered->getPages() as $page) {
                $role->addPage($page);
            }
        }

        $this->aclService->saveRole($role);
    }
}
