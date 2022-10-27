<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro potvrzení registrace oddílu.
 */
interface ITroopConfirmFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): TroopConfirmForm;
}
