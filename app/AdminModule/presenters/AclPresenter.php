<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\IRolesGridControlFactory;
use App\AdminModule\Components\RolesGridControl;
use App\AdminModule\Forms\AddRoleForm;
use App\AdminModule\Forms\EditRoleForm;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Services\Authenticator;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\AbortException;
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
     * @var Authenticator
     * @inject
     */
    public $authenticator;


    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    public function renderEdit(int $id) : void
    {
        $role = $this->roleRepository->findById($id);

        $this->template->editedRole = $role;
    }

    /**
     * Zapne testování role.
     * @param $id
     * @throws AbortException
     */
    public function actionTest(int $id) : void
    {
        $role = $this->roleRepository->findById($id);

        $this->authenticator->updateRoles($this->getPresenter()->user, $role);

        $this->redirect(':Web:Page:default');
    }

    protected function createComponentRolesGrid() : RolesGridControl
    {
        return $this->rolesGridControlFactory->create();
    }

    protected function createComponentAddRoleForm() : \Nette\Application\UI\Form
    {
        $form = $this->addRoleFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) : void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Acl:default');
            }

            $this->flashMessage('admin.acl.roles_saved', 'success');

            $id = $this->roleRepository->findLastId();
            $this->redirect('Acl:edit', ['id' => $id]);
        };

        return $form;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    protected function createComponentEditRoleForm() : \Nette\Application\UI\Form
    {
        $form = $this->editRoleFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, array $values) : void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Acl:default');
            }

            $this->flashMessage('admin.acl.roles_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy()) {
                $id = $values['id'];
                $this->redirect('Acl:edit', ['id' => $id]);
            } else {
                $this->redirect('Acl:default');
            }
        };

        return $form;
    }
}
