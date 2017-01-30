<?php

namespace App\Model\CMS\Content;


use App\Model\CMS\Page;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
 * @ORM\Table(name="content")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "text_content" = "TextContent",
 *     "document_content" = "DocumentContent",
 *     "application_content" = "ApplicationContent",
 *     "html_content" = "HtmlContent",
 *     "faq_content" = "FaqContent",
 *     "news_content" = "NewsContent",
 *     "programs_content" = "ProgramsContent",
 *     "image_content" = "ImageContent",
 *     "users_content" = "UsersContent",
 *     "blocks_content" = "BlocksContent",
 *     "capacities_content" = "CapacitiesContent"
 * })
 */
abstract class Content
{
    const TEXT = 'text';
    const IMAGE = 'image';
    const DOCUMENT = 'document';
    const APPLICATION = 'application';
    const HTML = 'html';
    const FAQ = 'faq';
    const NEWS = 'news';
    const PROGRAMS = 'programs';
    const USERS = 'users';
    const BLOCKS = 'blocks';
    const CAPACITIES = 'capacities';

    const MAIN = 'main';
    const SIDEBAR = 'sidebar';

    public static $types = [
        self::TEXT,
        self::IMAGE,
        self::DOCUMENT,
        self::APPLICATION,
        self::HTML,
        self::FAQ,
        self::NEWS,
        self::PROGRAMS,
        self::USERS,
        self::BLOCKS,
        self::CAPACITIES
    ];

    public static $areas = [
        self::MAIN,
        self::SIDEBAR
    ];

    protected $type;

    use Identifier;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $heading;

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
     * @param string $heading
     * @param Page $page
     * @param string $area
     * @param int $position
     */
    public function __construct($heading, Page $page, $area, $position)
    {
        $this->heading = $heading;
        $this->page = $page;
        $this->area = $area;
        $this->position = $position;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTypeName()
    {
        return $this->type . 'Content';
    }
}