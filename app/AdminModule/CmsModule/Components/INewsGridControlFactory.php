<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

/**
 * Factory komponenty pro správu aktualit.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface INewsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : NewsGridControl;
}
