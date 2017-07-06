<?php

namespace App\Services;

use Nette;
use Nette\Utils\Image;


/**
 * Služba pro správu nahraných souborů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FilesService extends Nette\Object
{
    /** @var string */
    private $dir;


    /**
     * FilesService constructor.
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Uloží soubor.
     * @param $file
     * @param $path
     */
    public function save($file, $path)
    {
        $file->move($this->dir . $path);
    }

    /**
     * Odstraní soubor.
     * @param $path
     */
    public function delete($path)
    {
        $file = $this->dir . $path;
        if (file_exists($file))
            unlink($file);
    }

    /**
     * Vytvoří soubor s daným obsahem.
     * @param $path
     * @param $content
     */
    public function create($path, $content)
    {
        $file = fopen($this->dir . $path, 'wb' );
        fwrite($file, $content);
        fclose($file);
    }

     /**
     * Změní velikost obrázku.
     * @param $path
     * @param $width
     * @param $height
     */
    public function resizeImage($path, $width, $height)
    {
        $image = Image::fromFile($this->dir . $path);
        $image->resize($width, $height);
        $image->sharpen();
        $image->save($this->dir . $path);
    }

    /**
     * Vrací cestu ke složce pro nahrávání souborů.
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }
}
