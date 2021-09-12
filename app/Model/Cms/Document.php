<?php

declare(strict_types=1);

namespace App\Model\Cms;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita dokumentu.
 *
 * @ORM\Entity
 * @ORM\Table(name="document")
 */
class Document
{
    /**
     * Adresář pro ukládání dokumentů.
     */
    public const PATH = 'documents';
    use Id;

    /**
     * Tagy dokumentu.
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="documents", cascade={"persist"})
     *
     * @var Collection<int, Tag>
     */
    protected Collection $tags;

    /**
     * Název dokumentu.
     *
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * Adresa souboru.
     *
     * @ORM\Column(type="string")
     */
    protected string $file;

    /**
     * Popis.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * Datum změny souboru.
     *
     * @ORM\Column(type="datetime_immutable");
     */
    protected DateTimeImmutable $timestamp;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param Collection<int, Tag> $tags
     */
    public function setTags(Collection $tags): void
    {
        foreach ($this->tags as $tag) {
            $this->removeTag($tag);
        }

        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
    }

    public function addTag(Tag $tag): void
    {
        if (! $this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addDocument($this);
        }
    }

    public function removeTag(Tag $tag): void
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            $tag->removeDocument($this);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeImmutable $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
