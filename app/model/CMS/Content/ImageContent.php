<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;
use function file_exists;

/**
 * Entita obsahu s obrázkem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="image_content")
 */
class ImageContent extends Content implements IContent
{
    protected $type = Content::IMAGE;

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

    public static $aligns = [
        self::LEFT,
        self::RIGHT,
        self::CENTER,
    ];

    /**
     * Adresa obrázku.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $image;

    /**
     * Zarovnání obrázku v textu.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $align;

    /**
     * Šířka obrázku.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $width;

    /**
     * Výška obrázku.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $height;

    /** @var FilesService */
    private $filesService;

    public function injectFilesService(FilesService $filesService) : void
    {
        $this->filesService = $filesService;
    }

    /**
     * @return array
     */
    public static function getAligns() : array
    {
        return self::$aligns;
    }

    /**
     * @param array $aligns
     */
    public static function setAligns(array $aligns) : void
    {
        self::$aligns = $aligns;
    }

    public function getImage() : ?string
    {
        return $this->image;
    }

    public function setImage(?string $image) : void
    {
        $this->image = $image;
    }

    public function getAlign() : ?string
    {
        return $this->align;
    }

    public function setAlign(?string $align) : void
    {
        $this->align = $align;
    }

    public function getWidth() : ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width) : void
    {
        $this->width = $width;
    }

    public function getHeight() : ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height) : void
    {
        $this->height = $height;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);
        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addText('currentImage', 'admin.cms.pages_content_image_current_file')
            ->setAttribute('data-type', 'image')
            ->setAttribute('data-image', $this->image)
            ->setAttribute('data-width', $this->width)
            ->setAttribute('data-height', $this->height);

        $formContainer->addUpload('image', 'admin.cms.pages_content_image_new_file')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.pages_content_image_new_file_format');

        $formContainer->addSelect('align', 'admin.cms.pages_content_image_align', $this->prepareAlignOptions());

        $formContainer->addText('width', 'admin.cms.pages_content_image_width')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_image_size_note'))
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_image_width_format');

        $formContainer->addText('height', 'admin.cms.pages_content_image_height')
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_image_size_note'))
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_image_height_format');

        $formContainer->setDefaults([
            'align' => $this->align,
            'width' => $this->width,
            'height' => $this->height,
        ]);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param array $values
     * @throws UnknownImageFileException
     */
    public function contentFormSucceeded(Form $form, array $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];

        $file   = $values['image'];
        $width  = $values['width'] !== '' ? $values['width'] : null;
        $height = $values['height'] !== '' ? $values['height'] : null;

        $image = null;

        $exists = false;

        if ($file->size > 0) {
            $path        = $this->generatePath($file);
            $this->image = $path;
            $this->filesService->save($file, $path);
            $image  = $file->toImage();
            $exists = true;
        } elseif ($this->image) {
            $path   = $this->filesService->getDir() . $this->image;
            $exists = file_exists($path);
            if ($exists) {
                $image = Image::fromFile($path);
            }
        }

        if ($exists) {
            if ($width && $height) {
                $this->width  = $width;
                $this->height = $height;
            } elseif (! $width && ! $height) {
                $this->width  = $image->getWidth();
                $this->height = $image->getHeight();
            } elseif ($width) {
                $this->width  = $width;
                $this->height = ($image->getHeight() * $width) / $image->getWidth();
            } else {
                $this->width  = ($image->getWidth() * $height) / $image->getHeight();
                $this->height = $height;
            }
        } else {
            $this->width  = $width;
            $this->height = $height;
        }

        $this->align = $values['align'];
    }

    /**
     * Vrátí možnosti zarovnání obrázku pro select.
     * @return array
     */
    private function prepareAlignOptions() : array
    {
        $options = [];
        foreach (self::$aligns as $align) {
            $options[$align] = 'common.align.' . $align;
        }
        return $options;
    }

    /**
     * Vygeneruje cestu pro uložení obrázku.
     * @param $file
     */
    private function generatePath(FileUpload $file) : string
    {
        return '/images/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
