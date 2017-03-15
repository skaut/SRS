<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IDocumentsGridControlFactory;
use App\AdminModule\CMSModule\Components\IDocumentTagsGridControlFactory;

class DocumentsPresenter extends CMSBasePresenter
{
    /**
     * @var IDocumentsGridControlFactory
     * @inject
     */
    public $documentsGridControlFactory;

    /**
     * @var IDocumentTagsGridControlFactory
     * @inject
     */
    public $documentTagsGridControlFactory;


    protected function createComponentDocumentsGrid()
    {
        return $this->documentsGridControlFactory->create();
    }

    protected function createComponentDocumentTagsGrid()
    {
        return $this->documentTagsGridControlFactory->create();
    }
}