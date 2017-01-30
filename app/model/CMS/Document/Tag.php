<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
 * @ORM\Table(name="tag")
 */
class Tag
{
    use Identifier;

    /**
     * @ORM\ManyToMany(targetEntity="Document", mappedBy="tags", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $documents;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Tag constructor.
     */
    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }
}