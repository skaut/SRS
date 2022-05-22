<?php

declare(strict_types=1);

namespace App\Model\Cms;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita aktuality.
 */
#[ORM\Entity]
#[ORM\Table(name: 'news')]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    /**
     * Text aktuality.
     */
    #[ORM\Column(type: 'text')]
    protected string $text;

    /**
     * Datum publikování aktuality.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $published;

    /**
     * Připíchnutá nahoru.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $pinned = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getPublished(): DateTimeImmutable
    {
        return $this->published;
    }

    public function setPublished(DateTimeImmutable $published): void
    {
        $this->published = $published;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }
}
