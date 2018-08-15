<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Utils\Image;
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
     * @param $file
     * @param $path
     */
    public function save($file, $path) : void
    {
        $file->move($this->dir . $path);
    }

    /**
     * Odstraní soubor.
     * @param $path
     */
    public function delete($path) : void
    {
        $file = $this->dir . $path;
        if (! file_exists($file)) {
            return;
        }

        unlink($file);
    }

    /**
     * Vytvoří soubor s daným obsahem.
     * @param $path
     * @param $content
     */
    public function create($path, $content) : void
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
     * @param $path
     * @param $width
     * @param $height
     * @throws Nette\Utils\UnknownImageFileException
     */
    public function resizeImage($path, $width, $height) : void
    {
        $image = Image::fromFile($this->dir . $path);
        $image->resize($width, $height);
        $image->sharpen();
        $image->save($this->dir . $path);
    }

    /**
     * Změní velikost a ořízne obrázek.
     * @param $path
     * @param $width
     * @param $height
     * @throws Nette\Utils\UnknownImageFileException
     */
    public function resizeAndCropImage($path, $width, $height) : void
    {
        $image = Image::fromFile($this->dir . $path);

        if ($image->getWidth() / $width > $image->getHeight() / $height) {
            $image->resize(null, $height);
        } else {
            $image->resize($width, null);
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
