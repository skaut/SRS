<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Acl\RoleRepository;
use App\Model\Cms\Page;
use App\Model\Cms\PageException;
use App\Model\Cms\PageRepository;
use App\Services\AclService;
use App\Services\CmsService;
use Doctrine\ORM\NonUniqueResultException;
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
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;
use function array_keys;
use function count;

/**
 * Komponenta pro správu stránek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PagesGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var CmsService */
    private $cmsService;

    /** @var PageRepository */
    private $pageRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var AclService */
    private $aclService;

    public function __construct(
        ITranslator $translator,
        CmsService $cmsService,
        PageRepository $pageRepository,
        RoleRepository $roleRepository,
        AclService $aclService
    ) {
        $this->translator     = $translator;
        $this->cmsService     = $cmsService;
        $this->pageRepository = $pageRepository;
        $this->roleRepository = $roleRepository;
        $this->aclService     = $aclService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/pages_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentPagesGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('pagesGrid:sort!');
        $grid->setDataSource($this->pageRepository->createQueryBuilder('p')->orderBy('p.position'));
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.pages_name');

        $grid->addColumnText('slug', 'admin.cms.pages_slug');

        $grid->addColumnStatus('public', 'admin.cms.pages_public')
            ->addOption(false, 'admin.cms.pages_public_private')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.cms.pages_public_public')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeStatus'];

        $grid->addColumnText('roles', 'admin.cms.pages_roles', 'rolesText')
            ->setRendererOnCondition(function () {
                return $this->translator->translate('admin.cms.pages_roles_all');
            }, function (Page $page) {
                return count($this->roleRepository->findAll()) === $page->getRoles()->count();
            });

        $rolesOptions  = $this->aclService->getRolesWithoutRolesOptions([]);
        $publicOptions = [
            false => 'admin.cms.pages_public_private',
            true => 'admin.cms.pages_public_public',
        ];

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function (Container $container) use ($rolesOptions, $publicOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_name_empty');

            $container->addText('slug', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^[a-z0-9-]*$')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.pages_slug_exists', $this->pageRepository->findAllSlugs());

            $container->addMultiSelect('roles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->setDefaultValue(array_keys($rolesOptions))
                ->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicOptions);
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($rolesOptions, $publicOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_name_empty');

            $container->addText('slug', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^([a-z0-9-]*)|/$');

            $container->addMultiSelect('roles', '', $rolesOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicOptions);
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Page $item) : void {
            /** @var TextInput $slugText */
            $slugText = $container['slug'];
            $slugText->addRule(Form::IS_NOT_IN, 'admin.cms.pages_slug_exists', $this->pageRepository->findOthersSlugs($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'slug' => $item->getSlug(),
                'roles' => $this->roleRepository->findRolesIds($item->getRoles()),
                'public' => $item->isPublic() ? 1 : 0,
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('content', 'admin.cms.pages_edit_content', 'Pages:content')
            ->addParameters(['area' => 'main'])
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.pages_delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', static function (Page $item) {
            return $item->getSlug() !== '/';
        });
    }

    /**
     * Zpracuje přidání stránky.
     *
     * @throws AbortException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(stdClass $values) : void
    {
        $page = new Page($values->name, $values->slug);

        $page->setRoles($this->roleRepository->findRolesByIds($values->roles));
        $page->setPublic((bool) $values->public);

        $this->cmsService->savePage($page);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje upravení stránky.
     *
     * @throws AbortException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function edit(string $id, stdClass $values) : void
    {
        $page = $this->pageRepository->findById((int) $id);

        $page->setName($values->name);
        $page->setSlug($values->slug);
        $page->setRoles($this->roleRepository->findRolesByIds($values->roles));
        $page->setPublic((bool) $values->public);

        $this->cmsService->savePage($page);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění stránky.
     *
     * @throws AbortException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PageException
     */
    public function handleDelete(int $id) : void
    {
        $page = $this->pageRepository->findById($id);
        $this->cmsService->removePage($page);

        $this->getPresenter()->flashMessage('admin.cms.pages_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Přesune stránku s $item_id mezi $prev_id a $next_id.
     *
     * @throws AbortException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function handleSort(?string $item_id, ?string $prev_id, ?string $next_id) : void
    {
        $this->cmsService->sort((int) $item_id, (int) $prev_id, (int) $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $pagesGrid */
            $pagesGrid = $this['pagesGrid'];
            $pagesGrid->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní viditelnost stránky.
     *
     * @throws AbortException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function changeStatus(string $id, string $public) : void
    {
        $p = $this->getPresenter();

        $page = $this->pageRepository->findById((int) $id);

        if ($page->getSlug() === '/' && ! $public) {
            $p->flashMessage('admin.cms.pages_change_public_denied', 'danger');
        } else {
            $page->setPublic((bool) $public);
            $this->cmsService->savePage($page);

            $p->flashMessage('admin.cms.pages_changed_public', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $pagesGrid */
            $pagesGrid = $this['pagesGrid'];
            $pagesGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
