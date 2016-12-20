<?php

namespace App\WebModule\Presenters;

class PagePresenter extends WebBasePresenter
{
    public function renderDefault($pageId)
    {
        echo $pageId;
    }
}