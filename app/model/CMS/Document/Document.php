<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita dokumentu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="DocumentRepository")
 * @ORM\Table(name="document")
 */
class Document
{
    /**
     * Adresář pro ukládání dokumentů.
     */
    const PATH = "/documents";

    use Identifier;

    /**
     * Tagy dokumentu.
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="documents")
     * @var Collection
     */
    protected $tags;

    /**
     * Název dokumentu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Adresa souboru.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $file;

    /**
     * Popis.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $description;

    /**
     * Datum změny souboru.
     * @ORM\Column(type="datetime");
     * @var \DateTime
     */
    protected $timestamp;


    /**
     * Document constructor.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection $tags
     */
    public function setTags($tags)
    {
        $this->tags->clear();
        foreach ($tags as $tag)
            $this->tags->add($tag);
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
