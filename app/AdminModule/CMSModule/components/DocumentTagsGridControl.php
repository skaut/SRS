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
            $container->addText('name', '')
                ->addCondition(Form::FILLED) //->addRule(Form::FILLED, 'admin.cms.tags_name_empty') //TODO validace
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findAllNames());
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findOthersNames($item->getId()));

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
        else {
            $this->tagRepository->addTag($name);
            $p->flashMessage('admin.cms.tags_added', 'success');
        }

        $this->redirect('this');
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $this->tagRepository->editTag($id, $values['name']);
        $p->flashMessage('admin.cms.tags_edited', 'success');

        $this->redirect('this');
    }

    public function handleDelete($id)
    {
        $this->tagRepository->removeTag($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.tags_deleted', 'success');

        $this->redirect('this');
    }
}