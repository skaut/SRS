<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 */
class TextContent extends Content
{
    /** @ORM\Column(type="text", nullable=true) */
    protected $text;
}