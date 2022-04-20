<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu dokumentů
 */
interface IDocumentsGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): DocumentsGridControl;
}
