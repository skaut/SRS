<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\ImageContentDto;
use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;
use stdClass;

use function assert;

use const UPLOAD_ERR_OK;

/**
 * Entita obsahu s obrázkem.
 *
 * @ORM\Entity
 * @ORM\Table(name="image_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ImageContent extends Content implements IContent
{
    protected string $type = Content::IMAGE;

    /**
     * Zarovnání vlevo.
     */
    public const LEFT = 'left';

    /**
     * Zarovnání vpravo.
     */
    public const RIGHT = 'right';

    /**
     * Zarovnání na střed, bez obtékání.
     */
    public const CENTER = 'center';

    /** @var string[] */
    public static array $aligns = [
        self::LEFT,
        self::RIGHT,
        self::CENTER,
    ];

    /**
     * Adresa obrázku.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $image = null;

    /**
     * Zarovnání obrázku v textu.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $align = null;

    /**
     * Šířka obrázku.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $width = null;

    /**
     * Výška obrázku.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $height = null;

    private FilesService $filesService;

    public function injectFilesService(FilesService $filesService): void
    {
        $this->filesService = $filesService;
    }

    /**
     * @return string[]
     */
    public static function getAligns(): array
    {
        return self::$aligns;
    }

    /**
     * @param string[] $aligns
     */
    public static function setAligns(array $aligns): void
    {
        self::$aligns = $aligns;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function setAlign(?string $align): void
    {
        $this->align = $align;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);

        $formContainer->addUpload('image', 'admin.cms.pages.content.form.image')
            ->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true')
            ->setHtmlAttribute('data-initial-preview', '[' . ($this->image === null ? '' : '"' . $this->image . '"') . ']')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.pages.content.form.image_format');

        $formContainer->addSelect('align', 'admin.cms.pages.content.form.image_align', $this->prepareAlignOptions());

        $formContainer->addText('width', 'admin.cms.pages.content.form.image_width')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_image_size_note'))
            ->addCondition(Form::FILLED)->addRule(Form::INTEGER, 'admin.cms.pages_content_image_width_format');

        $formContainer->addText('height', 'admin.cms.pages.content.form.image_height')
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_image_size_note'))
            ->addCondition(Form::FILLED)->addRule(Form::INTEGER, 'admin.cms.pages_content_image_height_format');

        $formContainer->setDefaults([
            'align' => $this->align,
            'width' => $this->width,
            'height' => $this->height,
        ]);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     *
     * @throws UnknownImageFileException
     * @throws ImageException
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);

        $formName = $this->getContentFormName();
        $values   = $values->$formName;

        $file = $values->image;
        assert($file instanceof FileUpload);

        $width  = $values->width !== '' ? $values->width : null;
        $height = $values->height !== '' ? $values->height : null;

        $image = null;

        if ($file->getError() == UPLOAD_ERR_OK) {
            $this->image = $this->filesService->save($file, 'images', true, $file->name);
            $image       = $file->toImage();
        } elseif ($this->image) {
            $image = $this->filesService->openImage($this->image);
        }

        if ($image !== null) {
            if ($width && $height) {
                $this->width  = $width;
                $this->height = $height;
            } elseif (! $width && ! $height) {
                $this->width  = $image->getWidth();
                $this->height = $image->getHeight();
            } elseif ($width) {
                $this->width  = $width;
                $this->height = (int) ($image->getHeight() * $width / $image->getWidth());
            } else {
                $this->width  = (int) ($image->getWidth() * $height / $image->getHeight());
                $this->height = $height;
            }
        } else {
            $this->width  = $width;
            $this->height = $height;
        }

        $this->align = $values->align;
    }

    /**
     * Vrátí možnosti zarovnání obrázku pro select.
     *
     * @return string[]
     */
    private function prepareAlignOptions(): array
    {
        $options = [];
        foreach (self::$aligns as $align) {
            $options[$align] = 'common.align.' . $align;
        }

        return $options;
    }

    public function convertToDto(): ContentDto
    {
        return new ImageContentDto($this->getComponentName(), $this->heading, $this->image, $this->align, $this->width, $this->height);
    }
}
