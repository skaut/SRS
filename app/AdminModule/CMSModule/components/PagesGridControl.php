<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\Content;
use App\Model\CMS\PageRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;

class PagesGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(Translator $translator, PageRepository $pageRepository, RoleRepository $roleRepository)
    {
        $this->translator = $translator;
        $this->pageRepository = $pageRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/pages_grid.latte');
    }

    public function createComponentPagesGrid($name)
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


        $rolesChoices = $this->prepareRolesChoices();
        $publicChoices = [
            false => $this->translator->translate('admin.cms.pages_public_private'),
            true => $this->translator->translate('admin.cms.pages_public_public')
        ];

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($rolesChoices, $publicChoices) {
            $container->addText('name', '');
                //->addRule(Form::FILLED, 'admin.cms.pages_name_empty'); //TODO validace

            $container->addText('slug', '')
                ->addCondition(Form::FILLED) //->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^[a-z0-9-]*$')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.pages_slug_exists', $this->pageRepository->findAllSlugs());

            $rolesIds = array_keys($rolesChoices);

            $container->addMultiSelect('roles', '', $rolesChoices)->setAttribute('class', 'datagrid-multiselect')
                ->setDefaultValue($rolesIds);
                //->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicChoices);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) use($rolesChoices, $publicChoices) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_name_empty');

            $container->addText('slug', '')
                ->addRule(Form::FILLED, 'admin.cms.pages_slug_empty')
                ->addRule(Form::PATTERN, 'admin.cms.pages_slug_format', '^([a-z0-9-]*)|/$');

            $container->addMultiSelect('roles', '', $rolesChoices)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.pages_roles_empty');

            $container->addSelect('public', '', $publicChoices);
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
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
        $grid->allowRowsAction('delete', function($item) {
            return $item->getSlug() != '/';
        });
    }

    public function add($values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];
        $slug = $values['slug'];
        $roles = $this->roleRepository->findRolesByIds($values['roles']);

        if (!$name) {
            $p->flashMessage('admin.cms.pages_name_empty', 'danger');
        }
        elseif (!$slug) {
            $p->flashMessage('admin.cms.pages_slug_empty', 'danger');
        }
        elseif (count($roles) == 0) {
            $p->flashMessage('admin.cms.pages_roles_empty', 'danger');
        }
        else {
            $this->pageRepository->addPage($name, $slug, $roles, $values['public']);
            $p->flashMessage('admin.cms.pages_added', 'success');
        }

        $this->redirect('this');
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $this->pageRepository->editPage($id, $values['name'], $values['slug'], $this->roleRepository->findRolesByIds($values['roles']), $values['public']);
        $p->flashMessage('admin.cms.pages_edited', 'success');

        $this->redirect('this');
    }

    public function handleDelete($id)
    {
        $this->pageRepository->removePage($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_deleted', 'success');

        $this->redirect('this');
    }

    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->pageRepository->changePosition($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.pages_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['pagesGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function changeStatus($id, $public) {
        $p = $this->getPresenter();

        if ($this->pageRepository->findPageById($id)->getSlug() == '/' && !$public) {
            $p->flashMessage('admin.cms.pages_change_public_denied', 'danger');
        }
        else {
            $this->pageRepository->setPagePublic($id, $public);
            $p->flashMessage('admin.cms.pages_changed_public', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['pagesGrid']->redrawItem($id);
        }
        else {
            $this->redirect('this');
        }
    }

    private function prepareRolesChoices() {
        $choices = [];
        foreach ($this->roleRepository->findAll() as $role)
            $choices[$role->getId()] = $role->getName();
        return $choices;
    }
}