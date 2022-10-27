<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro výběr členů družiny.
 */
interface IGroupMembersFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(string $type, ?int $patrolId = null): GroupMembersForm;
}
