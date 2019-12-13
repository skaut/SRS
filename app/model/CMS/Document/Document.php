<?php

declare(strict_types=1);

namespace App\Model\CMS\Document;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

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
    public const PATH = '/documents';

    use Id;

    /**
     * Tagy dokumentu.
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="documents", cascade={"persist"})
     * @var Collection|Tag[]
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
     * @var DateTime
     */
    protected $timestamp;


    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags() : Collection
    {
        return $this->tags;
    }

    /**
     * @param Collection|Tag[] $tags
     */
    public function setTags(Collection $tags) : void
    {
        $this->tags->clear();
        foreach ($tags as $tag) {
            $this->tags->add($tag);
        }
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getFile() : string
    {
        return $this->file;
    }

    public function setFile(string $file) : void
    {
        $this->file = $file;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : void
    {
        $this->description = $description;
    }

    public function getTimestamp() : DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTime $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }
}
