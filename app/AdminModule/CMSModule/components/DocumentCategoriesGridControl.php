<?php

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\Document\CategoryDocument;
use App\Model\CMS\Document\CategoryDocumentRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu kategorií dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DocumentCategoriesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var CategoryDocumentRepository */
    private $categoryDocumentRepository;


    /**
     * DocumentCategoriesGridControl constructor.
     * @param Translator $translator
     * @param CategoryDocumentRepository $categoryDocumentRepository
     */
    public function __construct(Translator $translator, CategoryDocumentRepository $categoryDocumentRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->categoryDocumentRepository = $categoryDocumentRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/document_categories_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentDocumentCategoriesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->categoryDocumentRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.cms.categories_name');


        $grid->addInlineAdd()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.categories_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.categories_name_exists', $this->categoryDocumentRepository->findAllNames());
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.categories_name_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container['name']
                ->addRule(Form::IS_NOT_IN, 'admin.cms.categories_name_exists', $this->categoryDocumentRepository->findOthersNames($item->getId()));

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
                'data-content' => $this->translator->translate('admin.cms.categories_delete_confirm')
            ]);
    }

    /**
     * Zpracuje přidání štítku dokumentu.
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function add($values)
    {
        $category = new CategoryDocument();

        $category->setName($values['name']);

        $this->categoryDocumentRepository->save($category);

        $this->getPresenter()->flashMessage('admin.cms.categories_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu štítku dokumentu.
     * @param $id
     * @param $values
     * @throws \Nette\Application\AbortException
     */
    public function edit($id, $values)
    {
        $category = $this->categoryDocumentRepository->findById($id);

        $category->setName($values['name']);

        $this->categoryDocumentRepository->save($category);

        $this->getPresenter()->flashMessage('admin.cms.categories_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění štítku dokumentu.
     * @param $id
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $categoryDocument = $this->categoryDocumentRepository->findById($id);
        $this->categoryDocumentRepository->remove($categoryDocument);

        $this->getPresenter()->flashMessage('admin.cms.categories_deleted', 'success');

        $this->redirect('this');
    }
}
