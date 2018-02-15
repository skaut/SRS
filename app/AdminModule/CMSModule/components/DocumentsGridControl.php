<?php

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\Document\Document;
use App\Model\CMS\Document\DocumentRepository;
use App\Model\CMS\Document\CategoryDocumentRepository;
use App\Services\FilesService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DocumentsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var DocumentRepository */
    private $documentRepository;

    /** @var FilesService */
    private $filesService;

    /** @var CategoryDocumentRepository */
    private $categoryDocumentRepository;


    /**
     * DocumentsGridControl constructor.
     * @param Translator $translator
     * @param DocumentRepository $documentRepository
     * @param CategoryDocumentRepository $categoryDocumentRepository
     * @param FilesService $filesService
     */
    public function __construct(Translator $translator, DocumentRepository $documentRepository,
                                CategoryDocumentRepository $categoryDocumentRepository, FilesService $filesService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
        $this->categoryDocumentRepository = $categoryDocumentRepository;
        $this->filesService = $filesService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/documents_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentDocumentsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->documentRepository->createQueryBuilder('d'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);

        $grid->addColumnText('name', 'admin.cms.documents_name');

        $grid->addColumnText('documentCategories', 'admin.cms.documents_categories')
            ->setRenderer(function ($row) {
                $categories = Html::el();
                foreach ($row->getDocumentCategories() as $category) {
                    $categories->addHtml(Html::el('span')
                        ->setAttribute('class', 'label label-primary')
                        ->setText($category->getName()));
                    $categories->addHtml(Html::el()->setText(' '));
                }
                return $categories;
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
            ->setFormat('j. n. Y H:i');

        $documentCategoriesOptions = $this->categoryDocumentRepository->getDocumentCategoriesOptions();

        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($documentCategoriesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('documentCategories', '', $documentCategoriesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_categories_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload')
                ->addRule(Form::FILLED, 'admin.cms.documents_file_empty');

            $container->addText('description', '');
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($documentCategoriesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.documents_name_empty');

            $container->addMultiSelect('documentCategories', '', $documentCategoriesOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.cms.documents_categories_empty');

            $container->addUpload('file', '')->setAttribute('class', 'datagrid-upload');

            $container->addText('description', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container->setDefaults([
                'name' => $item->getName(),
                'documentCategories' => $this->categoryDocumentRepository->findCategoryDocumentByIds($item->getDocumentCategories()),
                'description' => $item->getDescription()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.documents_delete_confirm')
            ]);
    }

    /**
     * Zpracuje přidání dokumentu.
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $file = $values['file'];
        $path = $this->generatePath($file);
        $this->filesService->save($file, $path);

        $document = new Document();

        $document->setName($values['name']);
        $document->setDocumentCategories($this->categoryDocumentRepository->findDocumentCategoriesIds($values['documentCategories']));
        $document->setFile($path);
        $document->setDescription($values['description']);
        $document->setTimestamp(new \DateTime());

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu dokumentu.
     * @param $id
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function edit($id, $values)
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
        $document->setDocumentCategories($this->categoryDocumentRepository->findCategoryDocumentByIds($values['documentCategories']));
        $document->setDescription($values['description']);

        $this->documentRepository->save($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění dokumentu.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $document = $this->documentRepository->findById($id);
        $this->filesService->delete($document->getFile());
        $this->documentRepository->remove($document);

        $this->getPresenter()->flashMessage('admin.cms.documents_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Vygeneruje cestu dokumentu.
     * @param $file
     * @return string
     */
    private function generatePath($file)
    {
        return Document::PATH . '/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
