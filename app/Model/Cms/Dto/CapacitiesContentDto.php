<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s přehledem kapacit rolí.
 */
class CapacitiesContentDto extends ContentDto
{
    /** @param int[] $roles Role, jejichž obsazenosti se vypíší */
    public function __construct(string $type, string $heading, protected array $roles)
    {
        parent::__construct($type, $heading);
    }

    /** @return int[] */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
