<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu štítků dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDocumentTagsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return DocumentTagsGridControl
     */
    function create();
}