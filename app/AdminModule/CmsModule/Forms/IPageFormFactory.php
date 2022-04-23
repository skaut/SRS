<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Forms;

/**
 * Factory komponenty s formulářem pro úpravu obsahu stránky.
 */
interface IPageFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(int $id, string $area): PageForm;
}
