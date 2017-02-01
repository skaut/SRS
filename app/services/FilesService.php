<?php

namespace App\Services;


use Nette;

class FilesService extends Nette\Object
{
    private $dir;

    /**
     * FilesService constructor.
     * @param $dir
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function save($file, $path) {
        $file->move($this->dir . $path);
    }

    public function delete($path) {
        $file = $this->dir . $path;
        if(file_exists($file))
            unlink($file);
    }

    public function getDir() {
        return $this->dir;
    }
}