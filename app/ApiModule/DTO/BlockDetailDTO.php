<?php

namespace App\ApiModule\DTO;


use Nette;
use JMS\Serializer\Annotation as JMS;

class BlockDetailDTO extends Nette\Object
{
    /**
     * @JMS\Type("id")
     * @var int
     */
    private $id;

    private $name;

    private $tools;

    private $capacity;

    private $duration;

    private $perex;

    private $description;

    private $programsCount;

    private $categoryName;

    private $lectorName;

    private $lectorAbout;
}