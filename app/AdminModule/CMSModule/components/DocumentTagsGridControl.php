<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\CMS\Document\TagRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;

class DocumentTagsGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(Translator $translator, TagRepository $tagRepository)
    {
        $this->translator = $translator;
        $this->tagRepository = $tagRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/document_tags_grid.latte');
    }

    public function createComponentDocumentTagsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->tagRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.tags_name');

        $grid->addInlineAdd()->onControlAdd[] = function($container) {
            $container->addText('name', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addText('name', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'name' => $item->getName()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.tags_delete_confirm')
            ]);
    }

    public function add($values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.cms.tags_name_empty', 'danger');
        }
        elseif (!$this->tagRepository->isNameUnique($name)) {
            $p->flashMessage('admin.cms.tags_name_not_unique', 'danger');
        }
        else {
            $this->tagRepository->addTag($name);
            $p->flashMessage('admin.cms.tags_added', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['documentTagsGrid']->reload();
            $p->redrawControl('documentsGrid');
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.cms.tags_name_empty', 'danger');
        }
        elseif (!$this->tagRepository->isNameUnique($name, $id)) {
            $p->flashMessage('admin.cms.tags_name_not_unique', 'danger');
        }
        else {
            $this->tagRepository->editTag($id, $name);
            $p->flashMessage('admin.cms.tags_edited', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('documentsGrid');
        } else {
            $this->redirect('this');
        }
    }

    public function handleDelete($id)
    {
        $this->tagRepository->removeTag($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.tags_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['documentTagsGrid']->reload();
            $p->redrawControl('documentsGrid');
        } else {
            $this->redirect('this');
        }
    }
}