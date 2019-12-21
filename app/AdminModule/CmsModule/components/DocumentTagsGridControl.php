<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Acl\RoleRepository;
use App\Model\Cms\Document\Tag;
use App\Model\Cms\Document\TagRepository;
use App\Services\AclService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Localization\ITranslator;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;
use function array_keys;
use function count;

/**
 * Komponenta pro správu štítků dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentTagsGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var AclService */
    private $aclService;

    /** @var TagRepository */
    private $tagRepository;

    public function __construct(
        ITranslator $translator,
        RoleRepository $roleRepository,
        AclService $aclService,
        TagRepository $tagRepository
    ) {
        parent::__construct();

        $this->translator     = $translator;
        $this->roleRepository = $roleRepository;
        $this->aclService     = $aclService;
        $this->tagRepository  = $tagRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/document_tags_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentDocumentTagsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->tagRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.tags_name');

        $grid->addColumnText('roles', 'admin.cms.tags_roles', 'rolesText')
            ->setRendererOnCondition(function () {
                return $this->translator->translate('admin.cms.tags_roles_all');
            }, function (Tag $tag) {
                return count($this->roleRepository->findAll()) === $tag->getRoles()->count();
            });

        $rolesOptions = $this->aclService->getRolesWithoutRolesOptions([]);

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) use ($rolesOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findAllNames());
            $container->addMultiSelect('roles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->setDefaultValue(array_keys($rolesOptions))
                ->addRule(Form::FILLED, 'admin.cms.tags_roles_empty');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($rolesOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty');
            $container->addMultiSelect('roles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.tags_roles_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Tag $item) : void {
            /** @var TextInput $nameText */
            $nameText = $container['name'];
            $nameText->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findOthersNames($item->getId()));

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
                'data-content' => $this->translator->translate('admin.cms.tags_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání štítku dokumentu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function add(stdClass $values) : void
    {
        $tag = new Tag();

        $tag->setName($values->name);
        $tag->setRoles($this->roleRepository->findRolesByIds($values->roles));

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu štítku dokumentu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function edit(int $id, stdClass $values) : void
    {
        $tag = $this->tagRepository->findById($id);

        $tag->setName($values->name);
        $tag->setRoles($this->roleRepository->findRolesByIds($values->roles));

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění štítku dokumentu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $tag = $this->tagRepository->findById($id);
        $this->tagRepository->remove($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_deleted', 'success');

        $this->redirect('this');
    }
}
