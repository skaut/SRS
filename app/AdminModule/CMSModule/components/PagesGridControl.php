<?php

namespace App\AdminModule\CMSModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\CMS\Page;
use App\Model\CMS\PageRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu stránek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PagesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var PageRepository */
    private $pageRepository;

    /** @var RoleRepository */
    private $roleRepository;


    /**
     * PagesGridControl constructor.
     * @param Translator $translator
     * @param PageRepository $pageRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(Translator $translator, PageRepository $pageRepository, RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->pageRepository = $pageRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/pages_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridColumnStatusException
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentPagesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('pagesGrid:sort!');
        $grid->setDataSource($this->pageRepository->createQueryBuilder('p')->orderBy('p.position'));
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.cms.pages_name');

        $grid->addColumnText('slug', 'admin.cms.pages_slug');

        $grid->addColumnStatus('public', 'admin.cms.pages_public')
            ->addOption(FALSE, 'admin.cms.pages_public_private')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.cms.pages_public_public')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeStatus'];

        $grid->addColumnText('roles', 'admin.cms.pages_roles')
            ->setRenderer(function ($row) {
                if (count($this->roleRepository->findAll()) == count($row->getRoles()))
                    return $this->translator->translate('admin.cms.pages_roles_all');
                else {
                    $roles = [];
                    foreach ($row->getRoles() as $role) {
                        $roles[] = $role->getName();
                    }
                    return implode(", ", $roles);
                }
            });


        $rolesOptions = $this->roleRepository->getRolesWithoutRolesOptions([]);
        $publicOptions = [
            FALSE => 'admin.cms.pages_public_private',
            TRUE => 'admin.cms.pages_public_public'
        ];

        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($rolesOptions, $publicOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_name_empty');

            $container->addText('slug', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^[a-z0-9-]*$')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.pages_slug_exists', $this->pageRepository->findAllSlugs());

            $container->addMultiSelect('roles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->setDefaultValue(array_keys($rolesOptions))
                ->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicOptions);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($rolesOptions, $publicOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_name_empty');

            $container->addText('slug', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^([a-z0-9-]*)|/$');

            $container->addMultiSelect('roles', '', $rolesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicOptions);
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container['slug']
                ->addRule(Form::IS_NOT_IN, 'admin.cms.pages_slug_exists', $this->pageRepository->findOthersSlugs($item->getId()));

            $container->setDefaults([
                'name' => $item->getName(),
                'slug' => $item->getSlug(),
                'roles' => $this->roleRepository->findRolesIds($item->getRoles()),
                'public' => $item->isPublic() ? 1 : 0
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('content', 'admin.cms.pages_edit_content', 'Pages:content')
            ->addParameters(['area' => 'main'])
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.pages_delete_confirm')
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return $item->getSlug() != '/';
        });
    }

    /**
     * Zpracuje přidání stránky.
     * @param $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $page = new Page($values['name'], $values['slug']);

        $page->setRoles($this->roleRepository->findRolesByIds($values['roles']));
        $page->setPublic($values['public']);

        $this->pageRepository->save($page);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje upravení stránky.
     * @param $id
     * @param $values
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Nette\Application\AbortException
     */
    public function edit($id, $values)
    {
        $page = $this->pageRepository->findById($id);

        $page->setName($values['name']);
        $page->setSlug($values['slug']);
        $page->setRoles($this->roleRepository->findRolesByIds($values['roles']));
        $page->setPublic($values['public']);

        $this->pageRepository->save($page);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění stránky.
     * @param $id
     * @throws \App\Model\Page\PageException
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $page = $this->pageRepository->findById($id);
        $this->pageRepository->remove($page);

        $this->getPresenter()->flashMessage('admin.cms.pages_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Přesune stránku s $item_id mezi $prev_id a $next_id.
     * @param $item_id
     * @param $prev_id
     * @param $next_id
     * @throws \Nette\Application\AbortException
     */
    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->pageRepository->sort($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['pagesGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní viditelnost stránky.
     * @param $id
     * @param $public
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Nette\Application\AbortException
     */
    public function changeStatus($id, $public)
    {
        $p = $this->getPresenter();

        $page = $this->pageRepository->findById($id);

        if ($page->getSlug() == '/' && !$public) {
            $p->flashMessage('admin.cms.pages_change_public_denied', 'danger');
        } else {
            $page->setPublic($public);
            $this->pageRepository->save($page);

            $p->flashMessage('admin.cms.pages_changed_public', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['pagesGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
