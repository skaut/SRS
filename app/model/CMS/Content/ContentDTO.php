<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * Abstraktní DTO obsahu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ContentDTO
{
    /**
     * Typ obsahu.
     * @var string
     */
    protected $type;

    /**
     * Nadpis obsahu.
     * @var string
     */
    protected $heading;


    /**
     * @return mixed
     */
    public function getType() : string
    {
        return $this->type;
    }

    public function getHeading() : string
    {
        return $this->heading;
    }
}
