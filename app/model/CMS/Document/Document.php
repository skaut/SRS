<?php

namespace App\Model\CMS\Document;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="document")
 */
class Document
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\ManyToMany(targetEntity="Tag", inversedBy="documents", cascade={"persist"}) */
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