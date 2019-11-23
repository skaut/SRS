<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Model\CMS\Page;
use App\Model\Page\PageException;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;
use stdClass;

/**
 * Abstraktní entita obsahu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
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
 */
abstract class Content implements IContent
{
    /**
     * TextContent.
     * @var string
     */
    public const TEXT = 'text';

    /**
     * ImageContent.
     * @var string
     */
    public const IMAGE = 'image';

    /**
     * DocumentContent.
     * @var string
     */
    public const DOCUMENT = 'document';

    /**
     * ApplicationContent.
     * @var string
     */
    public const APPLICATION = 'application';

    /**
     * HtmlContent.
     * @var string
     */
    public const HTML = 'html';

    /**
     * FaqContent.
     * @var string
     */
    public const FAQ = 'faq';

    /**
     * NewsContent.
     * @var string
     */
    public const NEWS = 'news';

    /**
     * PlaceContent.
     * @var string
     */
    public const PLACE = 'place';

    /**
     * ProgramsContent.
     * @var string
     */
    public const PROGRAMS = 'programs';

    /**
     * UsersContent.
     * @var string
     */
    public const USERS = 'users';

    /**
     * LectorsContent.
     * @var string
     */
    public const LECTORS = 'lectors';

    /**
     * BlocksContent.
     * @var string
     */
    public const BLOCKS = 'blocks';

    /**
     * CapacitiesContent.
     * @var string
     */
    public const CAPACITIES = 'capacities';

    /**
     * OrganizerContent
     * @var string
     */
    public const ORGANIZER = 'organizer';


    /**
     * Hlavní oblast stránky.
     * @var string
     */
    public const MAIN = 'main';

    /**
     * Postranní panel stránky.
     * @var string
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
     * @var string
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
    public function addContentForm(Form $form) : Form
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
    public function contentFormSucceeded(Form $form, stdClass $values) : void
    {
        $values         = $values[$this->getContentFormName()];
        $this->position = $values['position'];
        $this->heading  = $values['heading'];
    }

    public function convertToDTO() : ContentDTO
    {
        return new ContentDTO($this->getComponentName(), $this->heading);
    }
}
