<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\Page;
use App\Model\Page\PageException;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;
use stdClass;

/**
 * Abstraktní entita obsahu.
 *
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
 *     "lectors_content" = "LectorsContent",
 *     "blocks_content" = "BlocksContent",
 *     "capacities_content" = "CapacitiesContent",
 *     "organizer_content" = "OrganizerContent"
 * })
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class Content implements IContent
{
    /**
     * TextContent.
     */
    public const TEXT = 'text';

    /**
     * ImageContent.
     */
    public const IMAGE = 'image';

    /**
     * DocumentContent.
     */
    public const DOCUMENT = 'document';

    /**
     * ApplicationContent.
     */
    public const APPLICATION = 'application';

    /**
     * HtmlContent.
     */
    public const HTML = 'html';

    /**
     * FaqContent.
     */
    public const FAQ = 'faq';

    /**
     * NewsContent.
     */
    public const NEWS = 'news';

    /**
     * PlaceContent.
     */
    public const PLACE = 'place';

    /**
     * ProgramsContent.
     */
    public const PROGRAMS = 'programs';

    /**
     * UsersContent.
     */
    public const USERS = 'users';

    /**
     * LectorsContent.
     */
    public const LECTORS = 'lectors';

    /**
     * BlocksContent.
     */
    public const BLOCKS = 'blocks';

    /**
     * CapacitiesContent.
     */
    public const CAPACITIES = 'capacities';

    /**
     * OrganizerContent
     */
    public const ORGANIZER = 'organizer';


    /**
     * Hlavní oblast stránky.
     */
    public const MAIN = 'main';

    /**
     * Postranní panel stránky.
     */
    public const SIDEBAR = 'sidebar';


    /** @var string[] */
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
        self::LECTORS,
        self::BLOCKS,
        self::CAPACITIES,
        self::ORGANIZER,
    ];

    /** @var string[] */
    public static $areas = [
        self::MAIN,
        self::SIDEBAR,
    ];

    /**
     * Typ obsahu.
     *
     * @var string
     */
    protected $type;
    use Identifier;

    /**
     * Nadpis obsahu.
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $heading;

    /**
     * Stránka, na které je obsah umístěn.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\CMS\Page", inversedBy="contents", cascade={"persist"})
     *
     * @var Page
     */
    protected $page;

    /**
     * Oblast stránky, ve které se obsah nachází.
     *
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $area;

    /**
     * Pořadí obsahu na stránce.
     *
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $position = 0;

    /**
     * @throws PageException
     */
    public function __construct(Page $page, string $area)
    {
        $this->page = $page;
        $this->area = $area;

        $contentsCount = $page->getContents($area)->count();

        $this->position = $contentsCount + 1;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getComponentName() : string
    {
        return $this->type . 'Content';
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getHeading() : string
    {
        return $this->heading;
    }

    public function setHeading(string $heading) : void
    {
        $this->heading = $heading;
    }

    public function getPage() : Page
    {
        return $this->page;
    }

    public function setPage(Page $page) : void
    {
        $this->page = $page;
    }

    public function getArea() : string
    {
        return $this->area;
    }

    public function setArea(string $area) : void
    {
        $this->area = $area;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(BaseForm $form) : BaseForm
    {
        $formName      = $this->getContentFormName();
        $formContainer = $form->addContainer($form->$formName);

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
            'heading' => $this->heading,
        ]);

        return $form;
    }

    public function getContentFormName() : string
    {
        return $this->type . '_' . $this->id;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(BaseForm $form, stdClass $values) : void
    {
        $formName       = $this->getContentFormName();
        $values         = $values->$formName;
        $this->position = $values->position;
        $this->heading  = $values->heading;
    }

    public function convertToDto() : ContentDto
    {
        return new ContentDto($this->getComponentName(), $this->heading);
    }
}
