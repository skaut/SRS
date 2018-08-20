<?php

declare(strict_types=1);

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * Entita aktuality.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="NewsRepository")
 * @ORM\Table(name="news")
 */
class News
{
    use Identifier;

    /**
     * Text aktuality.
     * @ORM\Column(type="text")
     * @var string
     */
    protected $text;

    /**
     * Datum publikování aktuality.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $published;

    /**
     * Připíchnutá nahoru.
     * @ORM\Column(type="boolean")
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

    public function getPublished() : \DateTime
    {
        return $this->published;
    }

    public function setPublished(\DateTime $published) : void
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
