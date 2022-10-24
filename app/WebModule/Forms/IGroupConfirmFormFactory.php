<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro potvrzení registrace družiny.
 */
interface IGroupConfirmFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(string $type, ?int $patrolId = null): GroupConfirmForm;
}
