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
    /**
     * @var \Nella\Doctrine\Repository
     */
    public $roleRepo;

    protected function createComponentUserGrid()
    {
        return new \SRS\Components\UserGrid($this->context->database);
    }

    public function startup() {
        parent::startup();
        $this->roleRepo = $this->context->database->getRepository('\SRS\Model\Acl\Role');
    }

    public function renderUsers() {

    }

    public function renderRoles() {
        $roles = $this->roleRepo->findAll();

        $this->template->roles = $roles;
    }

    public function renderAddRole() {

    }

    public function renderEditRole($id) {
        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('this');
        }

        $query = $this->context->database->createQuery("
            SELECT pp.id, pp.name from \SRS\Model\Acl\Permission pp WHERE pp NOT IN (
            SELECT p from \SRS\Model\Acl\Permission p INNER JOIN p.roles r WHERE r.id = ?1)
            ");
        $query->setParameter(1, isset($role->parent->id) ? $role->parent->id : null);
        $permissionsNotOwnedByParent = $query->getResult(); //umoznujeme pracovat jen s temi pravy, ktere jeste nema rodic
        $permissionFormChoices = array();
        $permissionFormDefaults = array();

        foreach ($permissionsNotOwnedByParent as $perm) {
            $permissionFormChoices[$perm['id']] = $perm['name'];
        }
        foreach ($role->permissions as $perm) {
            $permissionFormDefaults[] = $perm->id;
        }

        $form = $this->getComponent('roleForm');
        $this['roleForm']['permissions']->setItems($permissionFormChoices);
        $form->bindEntity($role);

//        $this['roleForm']['id']->setDefaultValue($role->id);
//        $this['roleForm']['name']->setDefaultValue($role->name);
//        $this['roleForm']['registerable']->setDefaultValue($role->registerable);
//        $this['roleForm']['registerableFrom']->setDefaultValue( $role->registerableTo ? $role->registerableTo->format('Y-m-d') : null);
//        $this['roleForm']['registerableTo']->setDefaultValue($role->registerableFrom ? $role->registerableFrom->format('Y-m-d') : null);

//        $this['roleForm']['permissions']->setDefaultValue($permissionFormDefaults);

        $this->template->role = $role;
    }

    public function handleDeleteRole($id) {
        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('this');
        }

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
        $this->flashMessage('Role smazána', 'success');
        $this->redirect('this');
    }



    protected function createComponentNewRoleForm($name)
    {
        $form = new \SRS\Form\NewRoleForm($parent = NULL, $name = NULL, $this->roleRepo->findAll());
        return $form;
    }

    protected function createComponentRoleForm($name) {
        $form = new \SRS\Form\RoleForm($parent = NULL, $name = NULL);
        return $form;
    }
}
