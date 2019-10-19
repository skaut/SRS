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
    /** @var string */
    protected $type = Content::CAPACITIES;

    /**
     * Role, jejichž obsazenosti se vypíší.
     * @var int[]
     */
    protected $roles;


    /**
     * @return int[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
