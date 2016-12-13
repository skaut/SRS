<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "Content" = "Content",
 *     "TextContent" = "TextContent",
 *     "DocumentContent" = "DocumentContent",
 *     "AttendeeBoxContent" = "AttendeeBoxContent",
 *     "HTMLContent" = "HTMLContent",
 *     "FAQContent" = "FAQContent",
 *     "NewsContent" = "NewsContent",
 *     "ProgramBoxContent" = "ProgramBoxContent",
 *     "ImageContent" = "ImageContent",
 *     "UserBoxBontent" = "UserBoxContent",
 *     "BlockBoxContent" = "BlockBoxContent",
 *     "CapacityBoxContent" = "CapacityBoxContent"
 * })
 */
abstract class Content implements IContent
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

    public static $types = [
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

    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    /**
     * Jednoznacny identifikator typu contentu
     */
    protected $content;

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="string", nullable=true) */
    protected $header;

    /** @ORM\Column(type="integer") */
    protected $position = 0;

    /** @ORM\Column(type="string") */
    protected $area;

    /** @ORM\ManyToOne(targetEntity="\SRS\Model\CMS\Page", inversedBy="contents", cascade={"persist"}) */
    protected $page;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param mixed $area
     */
    public function setArea($area)
    {
        $this->area = $area;
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    public function getFormIdentificator()
    {
        return "{$this->content}_{$this->id}";
    }

    public function addFormItems(Form $form)
    {
        $formContainer = $form->addContainer($this->getFormIdentificator());
        $formContainer->addHidden('id')->setDefaultValue($this->id)->getControlPrototype()->class('id');
        $formContainer->addHidden('position')->getControlPrototype()->class('order');
        $formContainer->addHidden('content')->setDefaultValue($this->content)->getControlPrototype()->class('content');
        return $form;
    }

    public function setValuesFromPageForm(Form $form)
    {
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];
        $this->position = $values['position'];
    }

    public function getContentName() {
        $this->translator->translate($this->content);
    }
}