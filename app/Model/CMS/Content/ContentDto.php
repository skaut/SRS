<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContentDto
{
    /**
     * Název komponenty.
     *
     * @var string
     */
    protected $componentName;

    /**
     * Nadpis obsahu.
     *
     * @var string
     */
    protected $heading;

    public function __construct(string $componentName, string $heading)
    {
        $this->componentName = $componentName;
        $this->heading       = $heading;
    }

    public function getComponentName() : string
    {
        return $this->componentName;
    }

    public function getHeading() : string
    {
        return $this->heading;
    }
}
