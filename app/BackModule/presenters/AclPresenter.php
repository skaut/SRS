<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:16
 * To change this template use File | Settings | File Templates.
 */
namespace BackModule;
class AclPresenter extends BasePresenter
{
    protected $roleRepo;

    protected function createComponentUserGrid()
    {
        return new \SRS\Components\UserGrid($this->context->database);
    }

    public function startup() {
        parent::startup();
        $this->roleRepo = $this->context->database->getRepository('\SRS\Model\Acl\Role');
    }

    public function renderList() {

    }

    public function renderRoles() {
        $roles = $this->roleRepo->findAll();

        $this->template->roles = $roles;
    }

    public function renderAddRole() {

    }

    public function handleDeleteRole($id) {
        $role = $this->roleRepo->find($id);
        if ($role->isSystem()) {
            $this->flashMessage('Systémovou roli nelze smazat', 'error');
            $this->redirect('this');
        }
        $roleRegistered = $this->roleRepo->findBy(array('name'=>'Registrovaný'));
        foreach ($role->users as $user) {
            $user->role = $roleRegistered;
        }
        $this->context->database->remove($role);
        $this->context->database->flush();
        $this->flashMessage('Role smazána');
    }
}
