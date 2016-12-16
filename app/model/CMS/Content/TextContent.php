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
    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;

    /**
     * TextContent constructor.
     * @param string $header
     * @param Page $page
     * @param string $area
     * @param int $position
     * @param string $text
     */
    public function __construct($header, $page, $area, $position, $text)
    {
        parent::__construct($header, $page, $area, $position);
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