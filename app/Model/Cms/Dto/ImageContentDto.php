<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s obrázkem
 */
class ImageContentDto extends ContentDto
{
    /**
     * @param string|null $image Adresa obrázku
     * @param string|null $align Zarovnání obrázku v textu
     */
    public function __construct(
        string $type,
        string $heading,
        protected ?string $image,
        protected ?string $align,
        protected ?int $width,
        protected ?int $height
    ) {
        parent::__construct($type, $heading);
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }
}
