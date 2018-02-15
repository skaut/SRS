<?php

namespace App\AdminModule\CMSModule\Components;


/**
 * Factory komponenty pro správu štítků dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
interface IDocumentCategoriesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return DocumentCategoriesGridControl
     */
    public function create();
}
