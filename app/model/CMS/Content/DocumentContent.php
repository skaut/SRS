<?php

namespace App\Model\CMS\Content;


use App\Model\CMS\Document\Tag;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 */
class DocumentContent extends Content
{
    protected $type = Content::DOCUMENT;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\CMS\Document\Tag", cascade={"persist"})
     * @var Tag
     */
    protected $tag;
}