<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Cms\Document;
use App\Model\Cms\Repositories\DocumentRepository;
use App\Model\Cms\Repositories\TagRepository;
use App\Services\FilesService;
use App\Utils\Helpers;
use DateTimeImmutable;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

use function assert;
use function basename;

use const UPLOAD_ERR_OK;

/**
 * Komponenta pro správu dokumentů
 */
class DocumentsGridControl extends Control
{
    public function __construct(
        private Translator $translator,
        private DocumentRepository $documentRepository,
        private TagRepository $tagRepository,
        private FilesService $filesService
    ) {
    }

    /**
     * Vykreslí komponentu
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/documents_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu
     *
     * @throws DataGridException
     */
    public function createComponentDocumentsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->documentRepository->createQueryBuilder('d'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.cms.documents.column.name');

        $grid->addColumnText('tags', 'admin.cms.documents.column.tags')
            ->setRenderer(static function (Document $row) {
                $tags = Html::el();
                foreach ($row->getTags() as $tag) {
                    $tags->addHtml(Html::el('span')
                        ->setAttribute('class', 'label label-primary')
                        ->setText($tag->getName()));
                    $tags->addHtml(Html::el()->setText(' '));
                }

                return $tags;
            });

        $grid->addColumnText('file', 'admin.cms.documents.column.file')
            ->setRenderer(static fn (Document $document) => Html::el('a')
                ->setAttribute('href', $document->getFile())
                ->setAttribute('target', '_blank')
                ->setAttribute('class', 'btn btn-xs btn-secondary')
                ->addHtml(Html::el('span')->setAttribute('class', 'fa fa-file-arrow-down'))
                ->addText(' ' . basename($document->getFile())));

        $grid->addColumnText('description', 'admin.cms.documents.column.description');

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents.column.timestamp')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $tagsOptions = $this->tagRepository->getTagsOptions();

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = static function (Container $container) use ($tagsOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents.column.name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents.column.tags_empty');

            $container->addUpload('file', '')->setHtmlAttribute('class', 'datagrid-upload')
                ->addRule(Form::FILLED, 'admin.cms.documents.column.file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($tagsOptions): void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents.column.name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents.column.tags_empty');

            $container->addUpload('file', '')->setHtmlAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Document $item): void {
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
                'data-content' => $this->translator->translate('admin.cms.documents.action.delete_confirm'),
            ]);
    }

    /**
     * Zpracuje přidání dokumentu
     */
    public function add(stdClass $values): void
    {
        $file = $values->file;
        $path = $this->filesService->save($file, Document::PATH, true, $file->name);

        $document = new Document();

        $document->setName($values->name);
        $document->setTags($this->tagRepository->findTagsByIds($values->tags));
        $document->setFile($path);
        $document->setDescription($values->description);
        $document->setTimestamp(new DateTimeImmutable());

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje úpravu dokumentu
     */
    public function edit(string $id, stdClass $values): void
    {
        $document = $this->documentRepository->findById((int) $id);

        $file = $values->file;
        assert($file instanceof FileUpload);
        if ($file->getError() === UPLOAD_ERR_OK) {
            $this->filesService->delete($this->documentRepository->findById((int) $id)->getFile());
            $path = $this->filesService->save($file, Document::PATH, true, $file->name);
            $document->setFile($path);
            $document->setTimestamp(new DateTimeImmutable());
        }

        $document->setName($values->name);
        $document->setTags($this->tagRepository->findTagsByIds($values->tags));
        $document->setDescription($values->description);

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents.message.save_success', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }

    /**
     * Zpracuje odstranění dokumentu
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $document = $this->documentRepository->findById($id);
        $this->filesService->delete($document->getFile());
        $this->documentRepository->remove($document);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.documents.message.delete_success', 'success');
        $p->redirect('this');
    }
}
