<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu
 */
class ContentDto
{
    /**
     * @param string $componentName Název komponenty
     * @param string $heading       Nadpis obsahu
     */
    public function __construct(protected string $componentName, protected string $heading)
    {
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }
}
