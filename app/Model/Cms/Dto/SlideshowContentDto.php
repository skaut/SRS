<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu se slideshow.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SlideshowContentDto extends ContentDto
{
    /**
     * Adresy obrázků.
     *
     * @var string[]
     */
    protected array $images;

    /**
     * @param string[] $images
     */
    public function __construct(string $type, string $heading, array $images)
    {
        parent::__construct($type, $heading);
        $this->images = $images;
    }

    /**
     * @return string[]
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
