<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu častých otázek
 */
interface IFaqGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): FaqGridControl;
}
