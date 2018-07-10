<?php
declare(strict_types=1);

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDocumentsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return DocumentsGridControl
     */
    public function create();
}
