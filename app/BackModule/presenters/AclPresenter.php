<?php
/**
 * Date: 30.10.12
 * Time: 21:16
 * Author: Michal Májský
 */
namespace BackModule;

/**
 * Presenter pro balicek ACL
 */
class AclPresenter extends BasePresenter
{
    /**
     * @var \Nella\Doctrine\Repository
     */
    public $roleRepo;

    protected $resource = 'Práva a role';

    public function startup()
    {
        parent::startup();
        $this->checkPermissions('Spravovat');
        $this->roleRepo = $this->context->database->getRepository('\SRS\Model\Acl\Role');
    }

    public function renderRoles()
    {

        $roles = $this->roleRepo->findAll();

        $this->template->roles = $roles;
    }

    public function renderAddRole()
    {

    }

    public function renderEditRole($id)
    {

        if ($id == null) {
            $this->redirect('roles');
        }

        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('roles');
        }


        $permissions = $this->context->database->getRepository('\SRS\Model\Acl\Permission')->findAll();
        $permissionFormChoices = array();
        foreach ($permissions as $perm) {
            $permissionFormChoices[$perm->id] = "{$perm->name} | {$perm->resource->name}";
        }

        $form = $this->getComponent('roleForm');
        $this['roleForm']['permissions']->setItems($permissionFormChoices);
        $form->bindEntity($role);

        $this->template->role = $role;
    }

    public function handleDeleteRole($id)
    {
        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('this');
        }

        if ($role->isSystem()) {
            $this->flashMessage('Systémovou roli nelze smazat', 'error');
            $this->redirect('this');
        }
        $roleRegistered = $this->roleRepo->findBy(array('name' => 'Registrovaný'));
        foreach ($role->users as $user) {
            $user->role = $roleRegistered;
        }
        $this->context->database->remove($role);
        $this->context->database->flush();
        $this->flashMessage('Role smazána', 'success');
        $this->redirect('this');
    }

    public function handleLoginAs($id)
    {
        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('this');
        } else {
            $user = $this->user;

            $user->identity->setRoles(array($role->name));
            $this->flashMessage('Přihlášení proběhlo úspěšně');
        }
        $this->redirect('this');

    }


    protected function createComponentNewRoleForm($name)
    {
        $form = new \SRS\Form\NewRoleForm($parent = NULL, $name = NULL, $this->roleRepo->findAll());
        return $form;
    }

    protected function createComponentRoleForm($name)
    {
        $form = new \SRS\Form\RoleForm($parent = NULL, $name = NULL);
        return $form;
    }
}
