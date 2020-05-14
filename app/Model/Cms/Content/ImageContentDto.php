<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu s obrázkem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ImageContentDto extends ContentDto
{
    /**
     * Adresa obrázku.
     */
    protected string $image;

    /**
     * Zarovnání obrázku v textu.
     */
    protected string $align;

    /**
     * Šířka obrázku.
     */
    protected int $width;

    /**
     * Výška obrázku.
     */
    protected int $height;

    public function __construct(string $type, string $heading, ?string $image, ?string $align, ?int $width, ?int $height)
    {
        parent::__construct($type, $heading);
        $this->image  = $image;
        $this->align  = $align;
        $this->width  = $width;
        $this->height = $height;
    }

    public function getImage() : ?string
    {
        return $this->image;
    }

    public function getAlign() : ?string
    {
        return $this->align;
    }

    public function getWidth() : ?int
    {
        return $this->width;
    }

    public function getHeight() : ?int
    {
        return $this->height;
    }
}
