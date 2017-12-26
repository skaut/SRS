<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\IRolesGridControlFactory;
use App\AdminModule\Forms\AddRoleForm;
use App\AdminModule\Forms\EditRoleForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use Nette\Forms\Form;


/**
 * Presenter obsluhující správu rolí.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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
     * @throws \Nette\Application\AbortException
     */
    public function startup()
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    /**
     * @param $id
     */
    public function renderEdit($id)
    {
        $role = $this->roleRepository->findById($id);

        $this->template->editedRole = $role;
    }

    /**
     * Zapne testování role.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function actionTest($id)
    {
        $role = $this->roleRepository->findById($id);

        $this->authenticator->updateRoles($this->getPresenter()->user, $role);

        $this->redirect(':Web:Page:default');
    }

    /**
     * @return \App\AdminModule\Components\RolesGridControl
     */
    protected function createComponentRolesGrid()
    {
        return $this->rolesGridControlFactory->create();
    }

    /**
     * @return \Nette\Application\UI\Form
     */
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

    /**
     * @return \Nette\Application\UI\Form
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
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
