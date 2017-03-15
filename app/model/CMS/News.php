<?php

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * @ORM\Entity(repositoryClass="NewsRepository")
 * @ORM\Table(name="news")
 */
class News
{
    use Identifier;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $text;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $published;


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
}