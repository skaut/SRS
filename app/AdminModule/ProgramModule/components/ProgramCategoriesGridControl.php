<?php

namespace App\AdminModule\ProgramModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu kategorií.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    /** @var ProgramService */
    private $programService;


    /**
     * ProgramCategoriesGridControl constructor.
     * @param Translator $translator
     * @param CategoryRepository $categoryRepository
     * @param RoleRepository $roleRepository
     * @param UserRepository $userRepository
     * @param ProgramRepository $programRepository
     * @param ProgramService $programService
     */
    public function __construct(Translator $translator, CategoryRepository $categoryRepository,
                                RoleRepository $roleRepository, UserRepository $userRepository,
                                ProgramRepository $programRepository, ProgramService $programService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->categoryRepository = $categoryRepository;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
        $this->programRepository = $programRepository;
        $this->programService = $programService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_categories_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentProgramCategoriesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);

        $grid->addColumnText('name', 'admin.program.categories_name');

        $grid->addColumnText('registerableRoles', 'admin.program.categories_registerable_roles', 'registerableRolesText');

        $rolesOptions = $this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]);

        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findAllNames());

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($rolesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty');

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
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

    /**
     * Zpracuje přidání kategorie.
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $category = new Category();

        $category->setName($values['name']);
        $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values['registerableRoles']));

        $this->categoryRepository->save($category);

        $p = $this->getPresenter();
        $p->flashMessage('admin.program.categories_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu kategorie.
     * @param $id
     * @param $values
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function edit($id, $values)
    {
        $category = $this->categoryRepository->findById($id);

        $this->categoryRepository->getEntityManager()->transactional(function ($em) use ($category, $values) {
            $category->setName($values['name']);
            $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values['registerableRoles']));

            $this->categoryRepository->save($category);

            $this->programService->updateUsersPrograms($this->userRepository->findAll());

            $this->categoryRepository->save($category);
        });

        $this->getPresenter()->flashMessage('admin.program.categories_saved', 'success');
        $this->redirect('this');
    }

    /**
     * Odstraní kategorii.
     * @param $id
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function handleDelete($id)
    {
        $category = $this->categoryRepository->findById($id);

        $this->categoryRepository->getEntityManager()->transactional(function ($em) use ($category) {
            $this->categoryRepository->remove($category);

            $this->programService->updateUsersPrograms($this->userRepository->findAll());
        });

        $this->getPresenter()->flashMessage('admin.program.categories_deleted', 'success');
        $this->redirect('this');
    }
}
