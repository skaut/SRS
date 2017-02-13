<?php

namespace App\AdminModule\Presenters;


use App\AdminModule\Components\IRolesGridControlFactory;
use App\AdminModule\ProgramModule\Forms\AddRoleForm;
use App\AdminModule\ProgramModule\Forms\EditRoleForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use Nette\Forms\Form;

class AclPresenter extends AdminBasePresenter
{
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


    public function startup()
    {
        parent::startup();

        if (!$this->user->isAllowed(Resource::ACL, Permission::MANAGE)) {
            $this->flashMessage('admin.common.access_denied', 'danger', 'lock');
            $this->redirect(":Web:Page:default");
        }
    }

    public function renderEdit($id) {
        $role = $this->roleRepository->findById($id);

        $this->template->editedRole = $role;
    }

    public function actionTest($id) {
        $role = $this->roleRepository->findById($id);

        //TODO

        $this->redirect('Web:default');
    }

    protected function createComponentRolesGrid($name)
    {
        return $this->rolesGridControlFactory->create();
    }

    protected function createComponentAddRoleForm($name)
    {
        $form = $this->addRoleFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.acl.roles_saved', 'success');

            $id = $this->roleRepository->findLastId();
            $this->redirect('Acl:edit', ['id' => $id]);
        };

        return $form;
    }

    protected function createComponentEditRoleForm($name)
    {
        $form = $this->editRoleFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.acl.roles_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'];
                $this->redirect('Acl:edit', ['id' => $id]);
            }
            else
                $this->redirect('Acl:default');
        };

        return $form;
    }
}