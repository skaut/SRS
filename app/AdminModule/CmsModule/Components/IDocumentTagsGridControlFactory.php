<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu štítků dokumentů.
 */
interface IDocumentTagsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): DocumentTagsGridControl;
}
