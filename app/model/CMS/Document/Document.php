<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * @ORM\Entity(repositoryClass="DocumentRepository")
 * @ORM\Table(name="document")
 */
class Document
{
    const PATH = "/documents";

    use Identifier;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="documents", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $tags;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $file;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="datetime");
     * @var \DateTime
     */
    protected $timestamp;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param ArrayCollection $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
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

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }


}