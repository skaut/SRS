<?php

namespace App\Model\CMS\Document;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Tag
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\ManyToMany(targetEntity="\App\Model\CMS\Documents\Document", mappedBy="tags", cascade={"persist"}) */
    protected $documents;

    /** @ORM\Column(type="string") */
    protected $name;
}