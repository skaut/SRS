<?php

namespace App\Model\CMS\Content;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_content")
 */
class ImageContent extends Content
{
    protected $type = Content::IMAGE;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $image;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $align;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $width;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $height;
}