<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Page;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette\Application\UI\Form;


/**
 * Abstraktní entita obsahu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
    /**
     * TextContent.
     */
    const TEXT = 'text';

    /**
     * ImageContent.
     */
    const IMAGE = 'image';

    /**
     * DocumentContent.
     */
    const DOCUMENT = 'document';

    /**
     * ApplicationContent.
     */
    const APPLICATION = 'application';

    /**
     * HtmlContent.
     */
    const HTML = 'html';

    /**
     * FaqContent.
     */
    const FAQ = 'faq';

    /**
     * NewsContent.
     */
    const NEWS = 'news';

    /**
     * PlaceContent.
     */
    const PLACE = 'place';

    /**
     * ProgramsContent.
     */
    const PROGRAMS = 'programs';

    /**
     * UsersContent.
     */
    const USERS = 'users';

    /**
     * BlocksContent.
     */
    const BLOCKS = 'blocks';

    /**
     * CapacitiesContent.
     */
    const CAPACITIES = 'capacities';


    /**
     * Hlavní oblast stránky.
     */
    const MAIN = 'main';

    /**
     * Postranní panel stránky.
     */
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

    /**
     * Typ obsahu.
     */
    protected $type;

    use Identifier;

    /**
     * Nadpis obsahu.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $heading;

    /**
     * Stránka, na které je obsah umístěn.
     * @ORM\ManyToOne(targetEntity="\App\Model\CMS\Page", inversedBy="contents", cascade={"persist"})
     * @var Page
     */
    protected $page;

    /**
     * Oblast stránky, ve které se obsah nachází.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $area;

    /**
     * Pořadí obsahu na stránce.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;


    /**
     * Content constructor.
     * @param Page $page
     * @param $area
     */
    public function __construct(Page $page, $area)
    {
        $this->page = $page;
        $this->area = $area;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
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

    /**
     * @return string
     */
    public function getContentFormName()
    {
        return $this->type . "_" . $this->id;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
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

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param \stdClass $values
     */
    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        $values = $values[$this->getContentFormName()];
        $this->position = $values['position'];
        $this->heading = $values['heading'];
    }
}
