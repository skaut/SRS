<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

/**
 * Factory komponenty pro správu slev.
 */
interface IDiscountsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): DiscountsGridControl;
}
