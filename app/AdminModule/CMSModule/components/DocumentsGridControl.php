<?php

namespace App\AdminModule\CMSModule\Components;


use App\Model\CMS\Document\DocumentRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
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

    public function __construct(Translator $translator, DocumentRepository $documentRepository)
    {
        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
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

        $grid->addColumnText('name', 'Název');

//        $grid->addColumnText('tags', 'Tagy');
//
//        $grid->addColumnText('description', 'Popis');
//
//        $grid->addInlineAdd()->onControlAdd[] = function($container) {
////            $container->addText('name', '');
////            $container->addSelect('type', '', $customInputTypesChoices);
//        };
//        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];
//
//        $grid->addInlineEdit()
//            ->onControlAdd[] = function($container) {
//            $container->addText('name', '');
//        };
//        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
//            $container->setDefaults([
//                'name' => $item->getName()
//            ]);
//        };
//        $grid->getInlineEdit()->setShowNonEditingColumns();
//        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];
//
//        $grid->addAction('delete', '', 'delete!')
//            ->setIcon('trash')
//            ->setTitle('admin.common.delete')
//            ->setClass('btn btn-xs btn-danger ajax')
//            ->setConfirm('admin.configuration.application_input_delete_confirm', 'name');
//
//        $grid->addAction('download', '', 'download!')
//            ->setIcon('download')
//            ->setTitle('admin.common.delete')
//            ->setClass('btn btn-xs btn-danger ajax')
//            ->setConfirm('admin.configuration.application_input_delete_confirm', 'name');
    }

//    public function add($values) {
////        switch ($values['type']) {
////            case 'text':
////                $this->customInputRepository->createText($values['name']);
////                break;
////
////            case 'checkbox':
////                $this->customInputRepository->createCheckBox($values['name']);
////                break;
////        }
////
////        $p = $this->getPresenter();
////        $p->flashMessage('admin.configuration.application_input_added', 'success');
////
////        if ($p->isAjax()) {
////            $p->redrawControl('flashes');
////            $this['customInputsGrid']->reload();
////        } else {
////            $this->redirect('this');
////        }
//    }
//
//    public function edit($id, $values)
//    {
////        $this->customInputRepository->renameInput($id, $values['name']);
////
////        $p = $this->getPresenter();
////        $p->flashMessage('admin.configuration.application_input_edited', 'success');
////
////        if ($p->isAjax()) {
////            $p->redrawControl('flashes');
////        } else {
////            $this->redirect('this');
////        }
//    }
//
//    public function handleDelete($id)
//    {
////        $this->customInputRepository->removeInput($id);
////
////        $p = $this->getPresenter();
////        $p->flashMessage('admin.configuration.application_input_deleted', 'success');
////
////        if ($p->isAjax()) {
////            $p->redrawControl('flashes');
////            $this['customInputsGrid']->reload();
////        } else {
////            $this->redirect('this');
////        }
//    }
//
//    public function handleDownload($id)
//    {
//
//    }
}