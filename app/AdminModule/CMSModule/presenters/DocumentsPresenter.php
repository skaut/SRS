<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\CMSModule\Components\DocumentsGridControl;
use App\AdminModule\CMSModule\Components\DocumentTagsGridControl;
use App\AdminModule\CMSModule\Components\IDocumentsGridControlFactory;
use App\AdminModule\CMSModule\Components\IDocumentTagsGridControlFactory;

/**
 * Presenter starající se o správu dokumentů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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

    protected function createComponentDocumentsGrid() : DocumentsGridControl
    {
        return $this->documentsGridControlFactory->create();
    }

    protected function createComponentDocumentTagsGrid() : DocumentTagsGridControl
    {
        return $this->documentTagsGridControlFactory->create();
    }
}
