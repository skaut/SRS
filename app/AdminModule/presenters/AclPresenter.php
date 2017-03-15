<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IRolesGridControlFactory;
use App\AdminModule\ProgramModule\Forms\AddRoleForm;
use App\AdminModule\ProgramModule\Forms\EditRoleForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Services\Authenticator;
use Nette\Forms\Form;

class AclPresenter extends AdminBasePresenter
{
    protected $resource = Resource::ACL;

    /**
     * @var AddRoleForm
     * @inject
     */
    public $addRoleFormFactory;

    /**
     * @var EditRoleForm
     * @inject
     */
    public $editRoleFormFactory;

    /**
     * @var IRolesGridControlFactory
     * @inject
     */
    public $rolesGridControlFactory;

    /**
     * @var Authenticator
     * @inject
     */
    public $authenticator;


    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function renderEdit($id)
    {
        $role = $this->roleRepository->findById($id);

        $this->template->editedRole = $role;
    }

    public function actionTest($id)
    {
        $role = $this->roleRepository->findById($id);

        $this->authenticator->updateRoles($this->getPresenter()->user, $role);

        $this->redirect(':Web:Page:default');
    }

    protected function createComponentRolesGrid()
    {
        return $this->rolesGridControlFactory->create();
    }

    protected function createComponentAddRoleForm()
    {
        $form = $this->addRoleFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy())
                $this->redirect('Acl:default');

            $this->flashMessage('admin.acl.roles_saved', 'success');

            $id = $this->roleRepository->findLastId();
            $this->redirect('Acl:edit', ['id' => $id]);
        };

        return $form;
    }

    protected function createComponentEditRoleForm()
    {
        $form = $this->editRoleFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy())
                $this->redirect('Acl:default');

            $this->flashMessage('admin.acl.roles_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'];
                $this->redirect('Acl:edit', ['id' => $id]);
            } else
                $this->redirect('Acl:default');
        };

        return $form;
    }
}