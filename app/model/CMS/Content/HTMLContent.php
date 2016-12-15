<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="htmlcontent")
 */
class HTMLContent extends Content
{
    /** @ORM\Column(type="text", nullable=true) */
    protected $text;
}