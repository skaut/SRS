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
    protected $pinned = FALSE;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return \DateTime
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param \DateTime $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return bool
     */
    public function isPinned()
    {
        return $this->pinned;
    }

    /**
     * @param bool $pinned
     */
    public function setPinned($pinned)
    {
        $this->pinned = $pinned;
    }
}
