<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Page;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette\Application\UI\Form;


/**
 * @ORM\Entity(repositoryClass="ContentRepository")
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
 *     "place_content" = "PlaceContent",
 *     "programs_content" = "ProgramsContent",
 *     "image_content" = "ImageContent",
 *     "users_content" = "UsersContent",
 *     "blocks_content" = "BlocksContent",
 *     "capacities_content" = "CapacitiesContent"
 * })
 */
abstract class Content implements IContent
{
    const TEXT = 'text';
    const IMAGE = 'image';
    const DOCUMENT = 'document';
    const APPLICATION = 'application';
    const HTML = 'html';
    const FAQ = 'faq';
    const NEWS = 'news';
    const PLACE = 'place';
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
        self::PLACE,
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


    public function __construct(Page $page, $area)
    {
        $this->page = $page;
        $this->area = $area;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getComponentName()
    {
        return $this->type . 'Content';
    }

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
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    /**
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param string $area
     */
    public function setArea($area)
    {
        $this->area = $area;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getContentFormName()
    {
        return $this->type . "_" . $this->id;
    }

    public function addContentForm(Form $form)
    {
        $formContainer = $form->addContainer($this->getContentFormName());

        $formContainer->addHidden('id')
            ->setAttribute('class', 'id');

        $formContainer->addHidden('position')
            ->setAttribute('class', 'position');

        $formContainer->addHidden('delete')
            ->setAttribute('class', 'delete');

        $formContainer->addText('heading', 'admin.cms.pages_content_heading');

        $formContainer->setDefaults([
            'id' => $this->id,
            'position' => $this->position,
            'delete' => 0,
            'heading' => $this->heading
        ]);

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        $values = $values[$this->getContentFormName()];
        $this->position = $values['position'];
        $this->heading = $values['heading'];
    }
}