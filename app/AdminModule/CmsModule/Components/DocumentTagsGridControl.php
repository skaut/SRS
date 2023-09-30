<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Cms\Repositories\TagRepository;
use App\Model\Cms\Tag;
use App\Services\AclService;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Localization\Translator;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function array_keys;
use function assert;
use function count;

/**
 * Komponenta pro správu štítků dokumentů.
 */
class DocumentTagsGridControl extends Control
{
    public function __construct(
        private readonly Translator $translator,
        private readonly RoleRepository $roleRepository,
        private readonly AclService $aclService,
        private readonly TagRepository $tagRepository,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/document_tags_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentDocumentTagsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->tagRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.documents.tags.column.name');

        $grid->addColumnText('roles', 'admin.cms.documents.tags.column.roles', 'rolesText')
            ->setRendererOnCondition(
                fn () => $this->translator->translate('admin.cms.documents.tags.column.roles_all'),
                fn (Tag $tag) => count($this->roleRepository->findAll()) === $tag->getRoles()->count()
            );

        $rolesOptions = $this->aclService->getRolesWithoutRolesOptions([]);

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) use ($rolesOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents.tags.column.name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.documents.tags.column.name_exists', $this->tagRepository->findAllNames());
            $container->addMultiSelect('roles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->setDefaultValue(array_keys($rolesOptions))
                ->addRule(Form::FILLED, 'admin.cms.documents.tags.column.roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($rolesOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents.tags.column.name_empty');
            $container->addMultiSelect('roles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents.tags.column.roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Tag $item): void {
            $nameText = $container['name'];
            assert($nameText instanceof TextInput);
            $nameText->addRule(Form::IS_NOT_IN, 'admin.cms.documents.tags.column.name_exists', $this->tagRepository->findOthersNames($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'roles' => $this->roleRepository->findRolesIds($item->getRoles()),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.documents.tags.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání štítku dokumentu.
     */
    public function add(stdClass $values): void
    {
        $tag = new Tag();

        $tag->setName($values->name);
        $tag->setRoles($this->roleRepository->findRolesByIds($values->roles));

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.documents.tags.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu štítku dokumentu.
     */
    public function edit(string $id, stdClass $values): void
    {
        $tag = $this->tagRepository->findById((int) $id);

        $tag->setName($values->name);
        $tag->setRoles($this->roleRepository->findRolesByIds($values->roles));

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.documents.tags.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje odstranění štítku dokumentu.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $tag = $this->tagRepository->findById($id);
        $this->tagRepository->remove($tag);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.documents.tags.message.delete_success', 'success');
        $p->redirect('this');
    }
}
