<?php

namespace App\WebModule\Presenters;

class PagePresenter extends WebBasePresenter {

    /**
     * @var \App\Model\CMS\PageRepository
     * @inject
     */
    public $pageRepository;

    public function actionDefault($pageId)
    {
        echo $pageId;
    }
}