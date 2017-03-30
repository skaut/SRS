<?php

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\Document\Tag;
use App\Model\CMS\Document\TagRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu štítků dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentTagsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var TagRepository */
    private $tagRepository;


    /**
     * DocumentTagsGridControl constructor.
     * @param Translator $translator
     * @param TagRepository $tagRepository
     */
    public function __construct(Translator $translator, TagRepository $tagRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->tagRepository = $tagRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/document_tags_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentDocumentTagsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->tagRepository->createQueryBuilder('t'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(FALSE);


        $grid->addColumnText('name', 'admin.cms.tags_name');


        $grid->addInlineAdd()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty')
                ->addRule(Form::IS_NOT_IN, 'admin.cms.tags_name_exists', $this->tagRepository->findAllNames());
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.cms.tags_name_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
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

    /**
     * Zpracuje přidání štítku dokumentu.
     * @param $values
     */
    public function add($values)
    {
        $tag = new Tag();

        $tag->setName($values['name']);

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu štítku dokumentu.
     * @param $id
     * @param $values
     */
    public function edit($id, $values)
    {
        $tag = $this->tagRepository->findById($id);

        $tag->setName($values['name']);

        $this->tagRepository->save($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění štítku dokumentu.
     * @param $id
     */
    public function handleDelete($id)
    {
        $tag = $this->tagRepository->findById($id);
        $this->tagRepository->remove($tag);

        $this->getPresenter()->flashMessage('admin.cms.tags_deleted', 'success');

        $this->redirect('this');
    }
}
