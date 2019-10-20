<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s přehledem kapacit rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CapacitiesContentDTO extends ContentDTO
{
    /**
     * Role, jejichž obsazenosti se vypíší.
     * @var int[]
     */
    protected $roles;


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
