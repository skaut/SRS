<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Components;

/**
 * Factory komponenty pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPaymentsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): PaymentsGridControl;
}
