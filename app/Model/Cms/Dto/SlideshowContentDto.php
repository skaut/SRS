<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu se slideshow
 */
class SlideshowContentDto extends ContentDto
{
    /**
     * @param string[] $images Adresy obrázků
     */
    public function __construct(string $type, string $heading, protected array $images)
    {
        parent::__construct($type, $heading);
    }

    /**
     * @return string[]
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
