<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Cms\Document\Document;
use App\Model\Cms\Document\DocumentRepository;
use App\Model\Cms\Document\TagRepository;
use App\Services\FilesService;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use stdClass;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;
use const UPLOAD_ERR_OK;

/**
 * Komponenta pro správu dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentsGridControl extends Control
{
    private ITranslator $translator;

    private DocumentRepository $documentRepository;

    private FilesService $filesService;

    private TagRepository $tagRepository;

    public function __construct(
        ITranslator $translator,
        DocumentRepository $documentRepository,
        TagRepository $tagRepository,
        FilesService $filesService
    ) {
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
        $this->template->setFile(__DIR__ . '/templates/documents_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
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

        $grid->addColumnText('file', 'admin.cms.documents_file')
            ->setRenderer(function (Document $row) {
                return Html::el('a')
                    ->setAttribute('href', $this->getPresenter()->getTemplate()->basePath
                        . '/files' . $row->getFile())
                    ->setAttribute('target', '_blank')
                    ->setAttribute('class', 'btn btn-xs btn-secondary')
                    ->addHtml(
                        Html::el('span')->setAttribute('class', 'fa fa-download')
                    );
            });

        $grid->addColumnText('description', 'admin.cms.documents_description');

        $grid->addColumnDateTime('timestamp', 'admin.cms.documents_timestamp')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $tagsOptions = $this->tagRepository->getTagsOptions();

        $grid->addInlineAdd()->setPositionTop()->onControlAdd[] = static function (Container $container) use ($tagsOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setHtmlAttribute('class', 'datagrid-upload')
                ->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[]                       = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[]  = static function (Container $container) use ($tagsOptions) : void {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('tags', '', $tagsOptions)->setHtmlAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_tags_empty');

            $container->addUpload('file', '')->setHtmlAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Document $item) : void {
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
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function add(stdClass $values) : void
    {
        $file = $values->file;
        $path = $this->generatePath($file);
        $this->filesService->save($file, $path);

        $document = new Document();

        $document->setName($values->name);
        $document->setTags($this->tagRepository->findTagsByIds($values->tags));
        $document->setFile($path);
        $document->setDescription($values->description);
        $document->setTimestamp(new DateTimeImmutable());

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu dokumentu.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function edit(string $id, stdClass $values) : void
    {
        $document = $this->documentRepository->findById((int) $id);

        /** @var FileUpload $file */
        $file = $values->file;
        if ($file->getError() == UPLOAD_ERR_OK) {
            $this->filesService->delete($this->documentRepository->find((int) $id)->getFile());
            $path = $this->generatePath($file);
            $this->filesService->save($file, $path);

            $document->setFile($path);
            $document->setTimestamp(new DateTimeImmutable());
        }

        $document->setName($values->name);
        $document->setTags($this->tagRepository->findTagsByIds($values->tags));
        $document->setDescription($values->description);

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění dokumentu.
     *
     * @throws ORMException
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
