<?php

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class News
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="text") */
    protected $text;

    /** @ORM\Column(type="date") */
    protected $published;

    /** @ORM\ManyToOne(targetEntity="\SRS\model\User") */
    protected $author;
}