<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="html_content")
 */
class HtmlContent extends Content
{
    protected $type = Content::HTML;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;
}