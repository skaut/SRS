<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu stránek
 */
interface IPagesGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): PagesGridControl;
}
