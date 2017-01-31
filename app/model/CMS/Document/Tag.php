<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity(repositoryClass="TagRepository")
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ArrayCollection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param ArrayCollection $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}