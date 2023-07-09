<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s obrázkem.
 */
class ImageContentDto extends ContentDto
{
    /**
     * @param ?string $image Adresa obrázku.
     * @param ?string $align Zarovnání obrázku v textu.
     */
    public function __construct(
        string $type,
        string $heading,
        protected string|null $image,
        protected string|null $align,
        protected int|null $width,
        protected int|null $height,
    ) {
        parent::__construct($type, $heading);
    }

    public function getImage(): string|null
    {
        return $this->image;
    }

    public function getAlign(): string|null
    {
        return $this->align;
    }

    public function getWidth(): int|null
    {
        return $this->width;
    }

    public function getHeight(): int|null
    {
        return $this->height;
    }
}
