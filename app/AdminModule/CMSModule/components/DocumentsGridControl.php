<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\TagRepository;
use App\Services\FilesService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;

class DocumentsGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    /**
     * @var FilesService
     */
    private $filesService;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(Translator $translator, DocumentRepository $documentRepository, TagRepository $tagRepository, FilesService $filesService)
    {
        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
        $this->tagRepository = $tagRepository;
        $this->filesService = $filesService;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/documents_grid.latte');
    }

    public function createComponentDocumentsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->documentRepository->createQueryBuilder('d')->orderBy('d.name'));

        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.documents_name');

        $grid->addColumnText('tags', 'admin.cms.documents_tags')
            ->setRenderer(function ($row) {
                $tags = Html::el();
                foreach ($row->getTags() as $tag) {
                    $tags->addHtml(Html::el('span')
                        ->setAttribute('class', 'label label-primary')
                        ->setText($tag->getName()));
                    $tags->addHtml(Html::el()->setText(' '));
                }
                return $tags;
            });

        $grid->addColumnText('file', 'admin.cms.documents_file')
            ->setRenderer(function ($row) {
                return Html::el('a')
                    ->setAttribute('href', '../../../files' . $row->getFile())
                    ->setAttribute('target', '_blank')
                    ->setText($this->translator->translate('admin.cms.documents_download'));
            });

        $grid->addColumnText('description', 'admin.cms.documents_description');

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents_timestamp')
            ->setFormat('j. n. Y - H:i');;

        $tagsChoices = $this->prepareTagsChoices();

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($tagsChoices) {
            $container->addText('name', '');
            $container->addMultiSelect('tags', '', $tagsChoices)->setAttribute('class', 'datagrid-multiselect');
            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');
            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) use($tagsChoices) {
            $container->addText('name', '');
            $container->addMultiSelect('tags', '', $tagsChoices)->setAttribute('class', 'datagrid-multiselect');
            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');
            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $tagsIds = array_map(function($o) { return $o->getId(); }, $item->getTags()->toArray());

            $container->setDefaults([
                'name' => $item->getName(),
                'tags' => $tagsIds,
                'description' => $item->getDescription()
            ]);
        };
        $grid->getInlineEdit()->setShowNonEditingColumns();
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('admin.cms.documents_delete_confirm', 'name');
    }

    public function add($values) {
        $p = $this->getPresenter();

        $name = $values['name'];
        $tags = $values['tags'];
        $file = $values['file'];
        $description = $values['description'];

        if (!$name) {
            $p->flashMessage('admin.cms.documents_name_empty', 'danger');
        }
        elseif (!$this->documentRepository->isNameUnique($name)) {
            $p->flashMessage('admin.cms.documents_name_not_unique', 'danger');
        }
        elseif (count($tags) == 0) {
            $p->flashMessage('admin.cms.documents_tags_empty', 'danger');
        }
        elseif (!$file->name) {
            $p->flashMessage('admin.cms.documents_file_empty', 'danger');
        }
        else {
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);
            $this->documentRepository->addDocument($name, $this->tagRepository->findTagsByIds($tags), $path, $description);
            $p->flashMessage('admin.cms.documents_added', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['documentsGrid']->reload();
            $p->redrawControl('documentTagsGrid');
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];
        $tags = $values['tags'];
        $file = $values['file'];
        $description = $values['description'];

        if (!$name) {
            $p->flashMessage('admin.cms.documents_name_empty', 'danger');
        }
        elseif (!$this->documentRepository->isNameUnique($name, $id)) {
            $p->flashMessage('admin.cms.documents_name_not_unique', 'danger');
        }
        elseif (count($tags) == 0) {
            $p->flashMessage('admin.cms.documents_tags_empty', 'danger');
        }
        else {
            if ($file->name) {
                $this->filesService->delete($this->documentRepository->find($id)->getFile());
                $path = $this->generatePath($file);
                $this->filesService->save($file, $path);
            }
            else {
                $path = null;
            }
            $this->documentRepository->editDocument($id, $name, $this->tagRepository->findTagsByIds($tags), $path, $description);
            $p->flashMessage('admin.cms.documents_edited', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $p->redrawControl('documentTagsGrid');
        } else {
            $this->redirect('this');
        }
    }

    public function handleDelete($id)
    {
        $this->filesService->delete($this->documentRepository->find($id)->getFile());
        $this->documentRepository->removeDocument($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.documents_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['documentsGrid']->reload();
            $p->redrawControl('documentTagsGrid');
        } else {
            $this->redirect('this');
        }
    }

    private function prepareTagsChoices() {
        $choices = [];
        foreach ($this->tagRepository->findTagsOrderedByName() as $tag)
            $choices[$tag->getId()] = $tag->getName();
        return $choices;
    }

    private function generatePath($file) {
        return Document::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}