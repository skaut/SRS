<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

use Ublaboo\DataGrid\DataGrid;

class ProgramCategoriesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var ProgramRepository */
    private $programRepository;

    public function __construct(Translator $translator, CategoryRepository $categoryRepository,
                                RoleRepository $roleRepository, UserRepository $userRepository,
                                ProgramRepository $programRepository)
    {
        $this->translator = $translator;
        $this->categoryRepository = $categoryRepository;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_categories_grid.latte');
    }

    public function createComponentProgramCategoriesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.categories_name');

        $grid->addColumnText('registerableRoles', 'admin.program.categories_registerable_roles')
            ->setRenderer(function ($row) {
                $roles = [];
                foreach ($row->getRegisterableRoles() as $role) {
                    $roles[] = $role->getName();
                }
                return implode(", ", $roles);
            });

        $rolesOptions = $this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]);

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findAllNames());

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) use($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty');

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'registerableRoles' => $this->roleRepository->findRolesIds($item->getRegisterableRoles())
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.categories_delete_confirm')
            ]);
    }

    public function add($values) {
        $category = new Category();

        $category->setName($values['name']);
        $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values['registerableRoles']));

        $this->categoryRepository->save($category);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.categories_saved', 'success');

        $this->redirect('this');
    }

    public function edit($id, $values)
    {
        $category = $this->categoryRepository->findById($id);

        $category->setName($values['name']);
        $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values['registerableRoles']));

        $this->programRepository->updateUsersPrograms($this->userRepository->findAll());

        $this->categoryRepository->save($category);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.categories_saved', 'success');

        $this->redirect('this');
    }

    public function handleDelete($id)
    {
        $category = $this->categoryRepository->findById($id);
        $this->categoryRepository->remove($category);

        $this->programRepository->updateUsersPrograms($this->userRepository->findAll());
        $this->programRepository->getEntityManager()->flush();

        $this->getPresenter()->flashMessage('admin.program.categories_deleted', 'success');

        $this->redirect('this');
    }
}