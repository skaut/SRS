<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

/**
 * Factory komponenty s formulářem pro nastavení platby.
 */
interface IPaymentFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): PaymentForm;
}
