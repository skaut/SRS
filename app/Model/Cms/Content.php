<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Exceptions\PageException;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use stdClass;

use function lcfirst;
use function str_replace;
use function ucwords;

/**
 * Abstraktní entita obsahu.
 *
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
 *     "place_content" = "PlaceContent",
 *     "programs_content" = "ProgramsContent",
 *     "image_content" = "ImageContent",
 *     "users_content" = "UsersContent",
 *     "lectors_content" = "LectorsContent",
 *     "blocks_content" = "BlocksContent",
 *     "capacities_content" = "CapacitiesContent",
 *     "organizer_content" = "OrganizerContent",
 *     "contact_form_content" = "ContactFormContent",
 *     "slideshow_content" = "SlideshowContent"
 * })
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
     * ContactFormContent
     */
    public const CONTACT_FORM = 'contact_form';

    /**
     * SlideshowContent
     */
    public const SLIDESHOW = 'slideshow';

    /**
     * Hlavní oblast stránky.
     */
    public const MAIN = 'main';

    /**
     * Postranní panel stránky.
     */
    public const SIDEBAR = 'sidebar';


    /** @var string[] */
    public static array $types = [
        self::TEXT,
        self::IMAGE,
        self::HTML,
        self::SLIDESHOW,
        self::NEWS,
        self::DOCUMENT,
        self::APPLICATION,
        self::PROGRAMS,
        self::CONTACT_FORM,
        self::FAQ,
        self::PLACE,
        self::USERS,
        self::LECTORS,
        self::BLOCKS,
        self::CAPACITIES,
        self::ORGANIZER,
    ];

    /** @var string[] */
    public static array $areas = [
        self::MAIN,
        self::SIDEBAR,
    ];

    /**
     * Typ obsahu.
     */
    protected string $type;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=FALSE)
     */
    private ?int $id;

    /**
     * Nadpis obsahu.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $heading = null;

    /**
     * Stránka, na které je obsah umístěn.
     *
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="contents", cascade={"persist"})
     */
    protected Page $page;

    /**
     * Oblast stránky, ve které se obsah nachází.
     *
     * @ORM\Column(type="string")
     */
    protected string $area;

    /**
     * Pořadí obsahu na stránce.
     *
     * @ORM\Column(type="integer")
     */
    protected int $position = 0;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function getComponentName(): string
    {
        return lcfirst(str_replace('_', '', ucwords($this->type, '_'))) . 'Content';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function setHeading(string $heading): void
    {
        $this->heading = $heading;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getArea(): string
    {
        return $this->area;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        $formName      = $this->getContentFormName();
        $formContainer = $form->addContainer($formName);

        $formContainer->addHidden('id')
            ->setHtmlAttribute('class', 'id');

        $formContainer->addHidden('position')
            ->setHtmlAttribute('class', 'position');

        $formContainer->addHidden('delete')
            ->setHtmlAttribute('class', 'delete');

        $formContainer->addText('heading', 'admin.cms.pages.content.form.heading');

        $formContainer->setDefaults([
            'id' => $this->id,
            'position' => $this->position,
            'delete' => 0,
            'heading' => $this->heading,
        ]);

        return $form;
    }

    public function getContentFormName(): string
    {
        return $this->type . '_' . $this->id;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        $formName       = $this->getContentFormName();
        $values         = $values->$formName;
        $this->position = (int) $values->position;
        $this->heading  = $values->heading;
    }

    public function convertToDto(): ContentDto
    {
        return new ContentDto($this->getComponentName(), $this->heading);
    }
}
