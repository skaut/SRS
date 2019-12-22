<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;
use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function is_dir;
use function mkdir;
use function unlink;

/**
 * Služba pro správu nahraných souborů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FilesService
{
    use Nette\SmartObject;

    /** @var string */
    private $dir;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    /**
     * Uloží soubor.
     */
    public function save(FileUpload $file, string $path) : void
    {
        $file->move($this->dir . $path);
    }

    /**
     * Odstraní soubor.
     */
    public function delete(string $path) : void
    {
        $file = $this->dir . $path;
        if (! file_exists($file)) {
            return;
        }

        unlink($file);
    }

    /**
     * Vytvoří soubor s daným obsahem.
     */
    public function create(string $path, string $content) : void
    {
        $absPath = $this->dir . $path;
        $dirname = dirname($absPath);

        if (! is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $file = fopen($absPath, 'wb');
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Změní velikost obrázku.
     *
     * @throws ImageException
     * @throws UnknownImageFileException
     */
    public function resizeImage(string $path, ?int $width, ?int $height) : void
    {
        $image = Image::fromFile($this->dir . $path);
        $dimensions = Image::calculateSize($image->getWidth(), $image->getHeight(), $width, $height);
        $image->resize($dimensions[0], $dimensions[1]);
        $image->sharpen();
        $image->save($this->dir . $path);
    }

    /**
     * Změní velikost a ořízne obrázek.
     *
     * @throws UnknownImageFileException
     * @throws ImageException
     */
    public function resizeAndCropImage(string $path, ?int $width, ?int $height) : void
    {
        $image = Image::fromFile($this->dir . $path);

        if ($image->getWidth() / $width > $image->getHeight() / $height) {
            $dimensions = Image::calculateSize($image->getWidth(), $image->getHeight(), null, $height);
            $image->resize($dimensions[0], $dimensions[1]);
        } else {
            $dimensions = Image::calculateSize($image->getWidth(), $image->getHeight(), $width, null);
            $image->resize($dimensions[0], $dimensions[1]);
        }

        $image->sharpen();

        $image->crop(($image->getWidth() - $width) / 2, ($image->getHeight() - $height) / 2, $width, $height);

        $image->save($this->dir . $path);
    }

    /**
     * Vrací cestu ke složce pro nahrávání souborů.
     */
    public function getDir() : string
    {
        return $this->dir;
    }
}
