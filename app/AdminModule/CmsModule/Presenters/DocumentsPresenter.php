<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Presenters;

use App\AdminModule\CmsModule\Components\DocumentsGridControl;
use App\AdminModule\CmsModule\Components\DocumentTagsGridControl;
use App\AdminModule\CmsModule\Components\IDocumentsGridControlFactory;
use App\AdminModule\CmsModule\Components\IDocumentTagsGridControlFactory;

/**
 * Presenter starající se o správu dokumentů.
 */
class DocumentsPresenter extends CmsBasePresenter
{
    /** @inject */
    public IDocumentsGridControlFactory $documentsGridControlFactory;

    /** @inject */
    public IDocumentTagsGridControlFactory $documentTagsGridControlFactory;

    protected function createComponentDocumentsGrid(): DocumentsGridControl
    {
        return $this->documentsGridControlFactory->create();
    }

    protected function createComponentDocumentTagsGrid(): DocumentTagsGridControl
    {
        return $this->documentTagsGridControlFactory->create();
    }
}
