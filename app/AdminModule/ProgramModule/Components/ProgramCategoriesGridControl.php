<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Program\Category;
use App\Model\Program\Commands\RemoveCategory;
use App\Model\Program\Commands\SaveCategory;
use App\Model\Program\Repositories\CategoryRepository;
use App\Services\AclService;
use App\Services\CommandBus;
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

use function assert;

/**
 * Komponenta pro správu kategorií.
 */
class ProgramCategoriesGridControl extends Control
{
    private CommandBus $commandBus;

    private ITranslator $translator;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private AclService $aclService;

    public function __construct(
        CommandBus $commandBus,
        ITranslator $translator,
        CategoryRepository $categoryRepository,
        RoleRepository $roleRepository,
        AclService $aclService
    ) {
        $this->commandBus         = $commandBus;
        $this->translator         = $translator;
        $this->categoryRepository = $categoryRepository;
        $this->roleRepository     = $roleRepository;
        $this->aclService         = $aclService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/program_categories_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentProgramCategoriesGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryRepository->createQueryBuilder('c'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.program.categories.column.name');

        $grid->addColumnText('registerableRoles', 'admin.program.categories.column.registerable_roles', 'registerableRolesText');

        $rolesOptions = $this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]);

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) use ($rolesOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories.column.name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.program.categories.column.name_exists', $this->categoryRepository->findAllNames());

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories.column.registerable_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($rolesOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.program.categories.column.name_empty');

            $container->addMultiSelect('registerableRoles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.program.categories.column.registerable_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Category $item): void {
            $nameText = $container['name'];
            assert($nameText instanceof TextInput);
            $nameText->addRule(Form::IS_NOT_IN, 'admin.program.categories.column.name_exists', $this->categoryRepository->findOthersNames($item->getId()));

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
                'data-content' => $this->translator->translate('admin.program.categories.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání kategorie.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function add(stdClass $values): void
    {
        $category = new Category($values->name);

        $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values->registerableRoles));

        $this->commandBus->handle(new SaveCategory($category, null));

        $this->getPresenter()->flashMessage('admin.program.categories.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu kategorie.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values): void
    {
        $category    = $this->categoryRepository->findById((int) $id);
        $categoryOld = clone $category;

        $category->setName($values->name);
        $category->setRegisterableRoles($this->roleRepository->findRolesByIds($values->registerableRoles));

        $this->commandBus->handle(new SaveCategory($category, $categoryOld));

        $this->getPresenter()->flashMessage('admin.program.categories.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Odstraní kategorii.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDelete(int $id): void
    {
        $category = $this->categoryRepository->findById($id);

        if ($category->getBlocks()->isEmpty()) {
            $this->commandBus->handle(new RemoveCategory($category));
            $this->getPresenter()->flashMessage('admin.program.categories.message.delete_success', 'success');
        } else {
            $this->getPresenter()->flashMessage('admin.program.categories.message.delete_failed', 'danger');
        }

        $this->redirect('this');
    }
}
