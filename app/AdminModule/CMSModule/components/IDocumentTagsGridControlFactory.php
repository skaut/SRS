<?php
declare(strict_types=1);

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
    public function create(): DocumentTagsGridControl;
}
