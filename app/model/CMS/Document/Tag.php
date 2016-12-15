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

    /** @ORM\ManyToMany(targetEntity="Document", mappedBy="tags", cascade={"persist"}) */
    protected $documents;

    /** @ORM\Column(type="string") */
    protected $name;

    public function __construct()
    {
        $this->documents = new \Doctrine\Common\Collections\ArrayCollection();
    }
}