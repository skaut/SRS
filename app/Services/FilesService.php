<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;

use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function unlink;

/**
 * Služba pro správu nahraných souborů.
 */
class FilesService
{
    use Nette\SmartObject;

    public function __construct(private readonly string $dir)
    {
    }

    /**
     * Uloží soubor z formuláře.
     */
    public function save(FileUpload $file, string $directory, bool $randomSubDir, string $fileName): string
    {
        $path         = $this->generatePath($directory, $randomSubDir, $fileName);
        $absolutePath = $this->getAbsolutePath($path);

        $file->move($absolutePath);

        return $path;
    }

    /**
     * Odstraní soubor.
     */
    public function delete(string $path): void
    {
        $absolutePath = $this->getAbsolutePath($path);
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }

    /**
     * Zapíše obsah do souboru.
     */
    public function create(string $content, string $directory, bool $randomSubDir, string $fileName): string
    {
        $path         = $this->generatePath($directory, $randomSubDir, $fileName);
        $absolutePath = $this->getAbsolutePath($path);

        $file = fopen($absolutePath, 'wb');
        fwrite($file, $content);
        fclose($file);

        return $path;
    }

    /**
     * Načte obrázek ze souboru.
     *
     * @throws UnknownImageFileException
     */
    public function openImage(string $path): Image|null
    {
        $absolutePath = $this->getAbsolutePath($path);

        if (file_exists($absolutePath)) {
            return Image::fromFile($absolutePath);
        }

        return null;
    }

    /**
     * Změní velikost obrázku.
     *
     * @throws ImageException
     * @throws UnknownImageFileException
     */
    public function resizeImage(string $path, int|null $width, int|null $height): void
    {
        $absolutePath = $this->getAbsolutePath($path);

        $image = Image::fromFile($absolutePath);
        $image->resize($width, $height);
        $image->sharpen();
        $image->save($absolutePath);
    }

    /**
     * Změní velikost a ořízne obrázek.
     *
     * @throws UnknownImageFileException
     * @throws ImageException
     */
    public function resizeAndCropImage(string $path, int|null $width, int|null $height): void
    {
        $absolutePath = $this->getAbsolutePath($path);

        $image = Image::fromFile($absolutePath);

        if ($image->getWidth() / $width > $image->getHeight() / $height) {
            $image->resize(null, $height);
        } else {
            $image->resize($width, null);
        }

        $image->sharpen();
        $image->crop('50%', '50%', $width, $height);
        $image->save($absolutePath);
    }

    /**
     * Vrací celou cestu k souboru.
     */
    private function getAbsolutePath(string $path): string
    {
        return $this->dir . $path;
    }

    /**
     * Vygeneruje relativní cestu.
     */
    private function generatePath(string $directory, bool $randomSubDir, string $fileName): string
    {
        $path = '/files/' . $directory;

        if ($randomSubDir) {
            $path .= '/' . Random::generate(5);
        }

        return $path . '/' . Strings::webalize($fileName, '.');
    }
}
