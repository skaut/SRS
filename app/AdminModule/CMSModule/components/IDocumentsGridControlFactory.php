<?php

namespace App\AdminModule\CMSModule\Components;

interface IDocumentsGridControlFactory
{
    /**
     * @return DocumentsGridControl
     */
    function create();
}