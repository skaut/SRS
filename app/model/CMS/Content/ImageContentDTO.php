<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;
use function file_exists;

/**
 * DTO obsahu s obrázkem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ImageContentDTO extends ContentDTO
{
    /**
     * Adresa obrázku.
     * @var string
     */
    protected $image;

    /**
     * Zarovnání obrázku v textu.
     * @var string
     */
    protected $align;

    /**
     * Šířka obrázku.
     * @var int
     */
    protected $width;

    /**
     * Výška obrázku.
     * @var int
     */
    protected $height;


    /**
     * ImageContent constructor.
     * @param string $type
     * @param string $heading
     * @param string $image
     * @param string $align
     * @param int $width
     * @param int $height
     */
    public function __construct(string $type, string $heading, ?string $image, ?string $align, ?int $width, ?int $height)
    {
        parent::__construct($type, $heading);
        $this->image = $image;
        $this->align = $align;
        $this->width = $width;
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
