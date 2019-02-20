<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\TagRepository;
use App\Services\FilesService;
use App\Utils\Helpers;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var DocumentRepository */
    private $documentRepository;

    /** @var FilesService */
    private $filesService;

    /** @var TagRepository */
    private $tagRepository;


    public function __construct(
        Translator $translator,
        DocumentRepository $documentRepository,
        TagRepository $tagRepository,
        FilesService $filesService
    ) {
        parent::__construct();

        $this->translator         = $translator;
        $this->documentRepository = $documentRepository;
        $this->tagRepository      = $tagRepository;
        $this->filesService       = $filesService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/documents_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridException
     */
    public function createComponentDocumentsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->documentRepository->createQueryBuilder('d'));
        $grid->setDefaultSort(['name' => 'ASC']);
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
                    ->setAttribute('href', $this->getPresenter()->getTemplate()->basePath
                        . '/files' . $row->getFile())
                    ->setAttribute('target', '_blank')
                    ->setAttribute('class', 'btn btn-xs btn-default')
                    ->addHtml(
                        Html::el('span')->setAttribute('class', 'fa fa-download')
                    );
            });

        $grid->addColumnText('description', 'admin.cms.documents_description');

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents_timestamp')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $tagsOptions = $this->tagRepository->getTagsOptions();

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = function ($container) use ($tagsOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload')
                ->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[]     = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = function ($container) use ($tagsOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) : void {
            $container->setDefaults([
                'name' => $item->getName(),
                'tags' => $this->tagRepository->findTagsIds($item->getTags()),
                'description' => $item->getDescription(),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.documents_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání dokumentu.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function add(\stdClass $values) : void
    {
        $file = $values['file'];
        $path = $this->generatePath($file);
        $this->filesService->save($file, $path);

        $document = new Document();

        $document->setName($values['name']);
        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
        $document->setFile($path);
        $document->setDescription($values['description']);
        $document->setTimestamp(new \DateTime());

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu dokumentu.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function edit(int $id, \stdClass $values) : void
    {
        $document = $this->documentRepository->findById($id);

        $file = $values['file'];
        if ($file->size > 0) {
            $this->filesService->delete($this->documentRepository->find($id)->getFile());
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);

            $document->setFile($path);
            $document->setTimestamp(new \DateTime());
        }

        $document->setName($values['name']);
        $document->setTags($this->tagRepository->findTagsByIds($values['tags']));
        $document->setDescription($values['description']);

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění dokumentu.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $document = $this->documentRepository->findById($id);
        $this->filesService->delete($document->getFile());
        $this->documentRepository->remove($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Vygeneruje cestu dokumentu.
     */
    private function generatePath(FileUpload $file) : string
    {
        return Document::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
