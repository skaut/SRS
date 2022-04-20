<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

/**
 * Factory komponenty s formulářem pro úpravu slevy
 */
interface IDiscountFormFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(int $id): DiscountForm;
}
