<?php
/**
 * Date: 30.10.12
 * Time: 21:16
 * Author: Michal Májský
 */
namespace BackModule;
use SRS\Model\Acl\Role;

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

    public function renderList()
    {
        $roles = $this->roleRepo->findAll();

        $this->template->roles = $roles;
    }

    public function renderAdd()
    {

    }

    public function renderEdit($id)
    {
        if ($id == null) {
            $this->redirect('list');
        }

        $role = $this->roleRepo->find($id);

        if ($role == null) {
            $this->flashMessage('Tato role neexistuje', 'error');
            $this->redirect('list');
        }

        $form = $this->getComponent('roleForm');

        $permissions = $this->context->database->getRepository('\SRS\Model\Acl\Permission')->findAll();
        $permissionFormChoices = array();
        foreach ($permissions as $perm) {
            $permissionFormChoices[$perm->id] = "{$perm->name} | {$perm->resource->name}";
        }
        $this['roleForm']['permissions']->setItems($permissionFormChoices);

        $pages = $this->context->database->getRepository('\SRS\Model\CMS\Page')->findAll();
        $pagesFormChoices = array();
        foreach ($pages as $page) {
            $pagesFormChoices[$page->id] = $page->name;
        }
        $this['roleForm']['pages']->setItems($pagesFormChoices);

        $registerableRoles = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findRegisterable();
        $incompatibleRolesFormChoices = array();
        $requiredRolesFormChoices = array();
        foreach ($registerableRoles as $registerableRole) {
            if ($registerableRole != $role)
                $requiredRolesFormChoices[$registerableRole->id] = $incompatibleRolesFormChoices[$registerableRole->id] = $registerableRole->name;
        }
        $this['roleForm']['incompatibleRoles']->setItems($incompatibleRolesFormChoices);
        $this['roleForm']['requiredRoles']->setItems($requiredRolesFormChoices);

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
        $roleRegistered = $this->roleRepo->findOneBy(array('name' => Role::REGISTERED));
        foreach ($role->users as $dbuser) {
            $dbuser->removeRole($role->name);
            if ($dbuser->roles->isEmpty())
                $dbuser->addRole($roleRegistered);
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
        $form = new \SRS\Form\RoleForm($parent = NULL, $name = NULL, $this->context->database);
        return $form;
    }
}
