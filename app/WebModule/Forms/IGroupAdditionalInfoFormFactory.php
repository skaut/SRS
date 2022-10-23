<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro vyplnění doplňujících údajů o členech družiny.
 */
interface IGroupAdditionalInfoFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): GroupAdditionalInfoForm;
}
