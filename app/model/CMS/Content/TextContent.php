<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Page;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 */
class TextContent extends Content
{
    protected $type = Content::TEXT;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;

    /**
     * TextContent constructor.
     * @param string $heading
     * @param Page $page
     * @param string $area
     * @param int $position
     * @param string $text
     */
    public function __construct($heading, $page, $area, $position, $text)
    {
        parent::__construct($heading, $page, $area, $position);
        $this->text = $text;
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
}