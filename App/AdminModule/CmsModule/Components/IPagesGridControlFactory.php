<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu stránek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPagesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : PagesGridControl;
}
