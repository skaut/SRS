<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu s přehledem kapacit rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CapacitiesContentDto extends ContentDto
{
    /**
     * Role, jejichž obsazenosti se vypíší.
     *
     * @var int[]
     */
    protected array $roles;

    /**
     * @param int[] $roles
     */
    public function __construct(string $type, string $heading, array $roles)
    {
        parent::__construct($type, $heading);
        $this->roles = $roles;
    }

    /**
     * @return int[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
