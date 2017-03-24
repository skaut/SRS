<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Rozhraní komponenty pro správu dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDocumentsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return DocumentsGridControl
     */
    function create();
}