<?php

namespace App\Model\CMS\Document;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tag")
 */
class Tag
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\ManyToMany(targetEntity="\App\Model\CMS\Documents\Document", mappedBy="tags", cascade={"persist", "remove"}) */
    protected $documents;

    /** @ORM\Column(type="string") */
    protected $name;

    public function __construct()
    {
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
    }
}