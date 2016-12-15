<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_content")
 */
class ImageContent extends Content
{
    /** @ORM\Column(type="string", nullable=true) */
    protected $image;

    /** @ORM\Column(type="string", nullable=true) */
    protected $align;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $width;

    /** @ORM\Column(type="integer", nullable=true) */
    protected $height;
}