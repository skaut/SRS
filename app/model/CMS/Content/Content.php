<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Page;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="content")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="content", type="string")
 * @ORM\DiscriminatorMap({
 *     "text_content" = "TextContent",
 *     "document_content" = "DocumentContent",
 *     "attendee_box_content" = "AttendeeBoxContent",
 *     "html_content" = "HTMLContent",
 *     "faq_content" = "FAQContent",
 *     "news_content" = "NewsContent",
 *     "program_box_content" = "ProgramBoxContent",
 *     "image_content" = "ImageContent",
 *     "user_box_content" = "UserBoxContent",
 *     "block_box_content" = "BlockBoxContent",
 *     "capacity_box_bontent" = "CapacityBoxContent"
 * })
 */
abstract class Content
{
    const TEXT = 'text';
    const IMAGE = 'image';
    const DOCUMENT = 'document';
    const ATTENDEE_BOX = 'attendee_box';
    const HTML = 'html';
    const FAQ = 'faq';
    const NEWS = 'news';
    const PROGRAM_BOX = 'program_box';
    const USER_BOX = 'user_box';
    const BLOCK_BOX = 'block_box';
    const CAPACITY_BOX = 'capacity_box';

    const MAIN = 'main';
    const SIDEBAR = 'sidebar';

    public static $contents = [
        self::TEXT,
        self::IMAGE,
        self::DOCUMENT,
        self::ATTENDEE_BOX,
        self::HTML,
        self::FAQ,
        self::NEWS,
        self::PROGRAM_BOX,
        self::USER_BOX,
        self::BLOCK_BOX,
        self::CAPACITY_BOX
    ];

    public static $areas = [
        self::MAIN,
        self::SIDEBAR
    ];

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $header;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\CMS\Page", inversedBy="contents", cascade={"persist"})
     * @var Page
     */
    protected $page;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $area;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * Content constructor.
     * @param string $header
     * @param Page $page
     * @param string $area
     * @param int $position
     */
    public function __construct($header, Page $page, $area, $position)
    {
        $this->header = $header;
        $this->page = $page;
        $this->area = $area;
        $this->position = $position;
    }
}