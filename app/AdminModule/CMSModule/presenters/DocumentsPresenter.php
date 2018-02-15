<?php

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\CMSModule\Components\IDocumentsGridControlFactory;
use App\AdminModule\CMSModule\Components\IDocumentCategoriesGridControlFactory;


/**
 * Presenter starající se o správu dokumentů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DocumentsPresenter extends CMSBasePresenter
{
    /**
     * @var IDocumentsGridControlFactory
     * @inject
     */
    public $documentsGridControlFactory;

    /**
     * @var IDocumentCategoriesGridControlFactory
     * @inject
     */
    public $documentCategoriesGridControlFactory;


    protected function createComponentDocumentsGrid()
    {
        return $this->documentsGridControlFactory->create();
    }

    protected function createComponentDocumentCategoriesGrid()
    {
        return $this->documentCategoriesGridControlFactory->create();
    }
}
