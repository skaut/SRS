<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

/**
 * Factory komponenty pro nastavení propojení se vzdělávací akcí
 */
interface ISkautIsEventEducationGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): SkautIsEventEducationGridControl;
}
