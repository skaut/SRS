<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro registraci oddílu.
 */
interface ITroopApplicationFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): TroopApplicationForm;
}
