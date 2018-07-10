<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;


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
    const LEFT = 'left';
    
    /**
     * Zarovnání vpravo.
     */
    const RIGHT = 'right';
    
    /**
     * Zarovnání na střed, bez obtékání.
     */
    const CENTER = 'center';
    
    public static $aligns = [
        self::LEFT,
        self::RIGHT,
        self::CENTER
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

    /**
     * @var FilesService
     */
    private $filesService;

    /**
     * @param FilesService $filesService
     */
    public function injectFilesService(FilesService $filesService)
    {
        $this->filesService = $filesService;
    }
    
    /**
     * @return array
     */
    public static function getAligns()
    {
        return self::$aligns;
    }

    /**
     * @param array $aligns
     */
    public static function setAligns($aligns)
    {
        self::$aligns = $aligns;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * @param string $align
     */
    public function setAlign($align)
    {
        $this->align = $align;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
    public function addContentForm(Form $form)
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
            'height' => $this->height
        ]);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param array $values
     * @throws \Nette\Utils\UnknownImageFileException
     */
    public function contentFormSucceeded(Form $form, array $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];

        $file = $values['image'];
        $width = $values['width'] !== '' ? $values['width'] : NULL;
        $height = $values['height'] !== '' ? $values['height'] : NULL;

        $image = NULL;

        $exists = FALSE;

        if ($file->size > 0) {
            $path = $this->generatePath($file);
            $this->image = $path;
            $this->filesService->save($file, $path);
            $image = $file->toImage();
            $exists = TRUE;
        } else if ($this->image) {
            $path = $this->filesService->getDir() . $this->image;
            $exists = file_exists($path);
            if ($exists)
                $image = Image::fromFile($path);
        }

        if ($exists) {
            if ($width && $height) {
                $this->width = $width;
                $this->height = $height;
            } elseif (!$width && !$height) {
                $this->width = $image->getWidth();
                $this->height = $image->getHeight();
            } elseif ($width) {
                $this->width = $width;
                $this->height = ($image->getHeight() * $width) / $image->getWidth();
            } else {
                $this->width = ($image->getWidth() * $height) / $image->getHeight();
                $this->height = $height;
            }
        } else {
            $this->width = $width;
            $this->height = $height;
        }

        $this->align = $values['align'];
    }

    /**
     * Vrátí možnosti zarovnání obrázku pro select.
     * @return array
     */
    private function prepareAlignOptions()
    {
        $options = [];
        foreach (ImageContent::$aligns as $align)
            $options[$align] = 'common.align.' . $align;
        return $options;
    }
    
    /**
     * Vygeneruje cestu pro uložení obrázku.
     * @param $file
     * @return string
     */
    private function generatePath($file)
    {
        return '/images/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}
