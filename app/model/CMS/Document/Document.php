<?php

namespace App\Model\CMS\Document;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="document")
 */
class Document
{
    const SAVE_PATH = '/files/documents/';

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\Documents\Tag", inversedBy="documents", cascade={"persist", "remove"}) */
    protected $tags;

    /** @ORM\Column(type="string") */
    protected $name;

    /** @ORM\Column(type="string") */
    protected $file;

    /** @ORM\Column(type="string", nullable=true) */
    protected $description;

    /** @ORM\Column(type="datetime"); */
    protected $timestamp;
}