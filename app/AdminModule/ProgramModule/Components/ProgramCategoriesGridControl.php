<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Services\AclService;
use App\Services\ProgramService;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Localization\ITranslator;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu kategorií.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramCategoriesGridControl extends Control
{
    private ITranslator $translator;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramService $programService;

    private AclService $aclService;

    public function __construct(
        ITranslator $translator,
        CategoryRepository $categoryRepository,
        RoleRepository $roleRepository,
        ProgramService $programService,
        AclService $aclService
    ) {
        $this->translator         = $translator;
        $this->categoryRepository = $categoryRepository;
        $this->roleRepository     = $roleRepository;
        $this->programService     = $programService;
        $this->aclService         = $aclService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/program_categories_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentProgramCategoriesGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.categories_name');

        $grid->addColumnText('registerableRoles', 'admin.program.categories_registerable_roles', 'registerableRolesText');

        $rolesOptions = $this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]);

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) use ($rolesOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findAllNames());

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($rolesOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories_name_empty');

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories_registerable_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Category $item) : void {
            /** @var TextInput $nameText */
            $nameText = $container['name'];
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.categories_name_exists', $this->categoryRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'registerableRoles' => $this->roleRepository->findRolesIds($item->getRegisterableRoles()),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.program.categories_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání kategorie.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function add(stdClass $values) : void
    {
        $this->programService->createCategory($values->name, $this->roleRepository->findRolesByIds($values->registerableRoles));

        $this->getPresenter()->flashMessage('admin.program.categories_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu kategorie.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values) : void
    {
        $category = $this->categoryRepository->findById((int) $id);

        $this->programService->updateCategory($category, $values->name, $this->roleRepository->findRolesByIds($values->registerableRoles));

        $this->getPresenter()->flashMessage('admin.program.categories_saved', 'success');
        $this->redirect('this');
    }

    /**
     * Odstraní kategorii.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDelete(int $id) : void
    {
        $category = $this->categoryRepository->findById($id);

        if ($category->getBlocks()->isEmpty()) {
            $this->categoryRepository->remove($category);
            $this->getPresenter()->flashMessage('admin.program.categories_deleted', 'success');
        } else {
            $this->getPresenter()->flashMessage('admin.program.categories_deleted_error', 'danger');
        }

        $this->redirect('this');
    }
}
