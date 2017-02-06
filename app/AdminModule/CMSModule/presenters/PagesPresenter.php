<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IPagesGridControlFactory;
use App\Model\CMS\PageRepository;

class PagesPresenter extends CMSBasePresenter
{
    /**
     * @var IPagesGridControlFactory
     * @inject
     */
    public $pagesGridControlFactory;

    /**
     * @var PageRepository
     * @inject
     */
    public $pagesRepository;

    public function renderContent($id) {
        $page = $this->pagesRepository->findPageById($id);

        $this->template->page = $page;
    }

    protected function createComponentPagesGrid($name)
    {
        return $this->pagesGridControlFactory->create($name);
    }
}