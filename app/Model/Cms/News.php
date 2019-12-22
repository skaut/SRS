<?php

declare(strict_types=1);

namespace App\Model\Cms;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita aktuality.
 *
 * @ORM\Entity(repositoryClass="NewsRepository")
 * @ORM\Table(name="news")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class News
{
    use Id;

    /**
     * Text aktuality.
     *
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $text;

    /**
     * Datum publikování aktuality.
     *
     * @ORM\Column(type="datetime")
     *
     * @var DateTimeImmutable
     */
    protected $published;

    /**
     * Připíchnutá nahoru.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $pinned = false;

    public function getId() : int
    {
        return $this->id;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function setText(string $text) : void
    {
        $this->text = $text;
    }

    public function getPublished() : DateTimeImmutable
    {
        return $this->published;
    }

    public function setPublished(DateTimeImmutable $published) : void
    {
        $this->published = $published;
    }

    public function isPinned() : bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned) : void
    {
        $this->pinned = $pinned;
    }
}
